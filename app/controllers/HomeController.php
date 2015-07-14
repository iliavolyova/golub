<?php

require 'vendor/autoload.php';

use Carbon\Carbon;

class HomeController extends BaseController {

    /*
     * posalji_poruku
     *
     * Pomoćna funkcija za slanje mailova kroz GMail API (koristi i PHPMailer).
     *
     * @param $service
     * @param $moja_adresa e-mail string
     * @param $primatelj e-mail string
     *
     * @param $subject subject string
     * @param $msg poruka kao običan string (funkcija će pripremiti mail sintaksu)
     */

    private function posalji_poruku($service, $moja_adresa, $primatelj, $subject, $msg)
    {
        $mail = new PHPMailer();
        $mail->CharSet = "UTF-8";
        $from = $moja_adresa;
        $fname = $moja_adresa;
        $mail->From = $from;
        $mail->FromName = $fname;
        $mail->AddAddress($primatelj); // primatelja
        $mail->AddReplyTo($from,$fname);
        $mail->Subject = $subject;
        $mail->Body    = $msg;
        $mail->preSend();
        $mime = $mail->getSentMIMEMessage();
        $m = new Google_Service_Gmail_Message();
        $data = base64_encode($mime);
        $data = str_replace(array('+','/','='),array('-','_',''),$data); // url safe
        $m->setRaw($data);
        $service->users_messages->send("me", $m); // me je magicna varijabla, ali svejedno treba $moja_adresa
    }

    /*
     * pohrani_poruku
     *
     * Pomoćna funkcija za pohranjivanje mail objekta u bazu podataka
     *
     * @param $service
     * @param $moja_adresa e-mail string
     * @param $category za buduću eventualnu organizaciju (sad je uvijek 'inbox', ne uključuje samo chat poruke)
     * @param $message Gmail_Message objekt
     *
     */

    private function pohrani_poruku($service, $moja_adresa, $category, $message)
    {
        $sender = ''; $sfull = '';
        $receiver = ''; $rfull = '';
        $hdrs = $message->getPayload()->getHeaders();
        $tsmp = 0; // timestamp

        // prolazimo headerima maila i izvlačimo osnovne informacije
        foreach ($hdrs as $komad) {
            if ($komad->getName() == "From")
                $sender = $komad->getValue();
            if ($komad->getName() == "To")
                $receiver = $komad->getValue();
            if ($komad->getName() == "Subject")
                $subject = $komad->getValue();
            if ($komad->getName() == "Date") {
                $trm = trim(substr($komad->getValue(), 0, 31));
                $date = DateTime::createFromFormat( 'D, d M Y H:i:s O', $trm);
                $tsmp =  (string)$date->getTimestamp();
            }
           // Log::info($komad->getName());
        }

        // novi redak u bazi
        $p = new Email;

        $sender = str_replace('"', '', $sender);
        $receiver = str_replace('"', '', $receiver);

        // neki mailovi imaju adrese oblika "ime prezime" <adresa>, neki samo adresa
        // testiramo prvo za sendera...
        if (strpos($sender,'<') !== false) {
            $pmail = strpos($sender, '<');
            $kmail = strpos($sender, '>');

            $sfull = substr($sender, 0, $pmail);
            $sender = substr($sender, $pmail + 1, $kmail - $pmail - 1);
        }
        else
        {
            $sfull = $sender;
        }

        // ... i potom za receivera
        if (strpos($receiver,'<') !== false) {
            $pmail = strpos($receiver, '<');
            $kmail = strpos($receiver, '>');

            $rfull = substr($receiver, 0, $pmail);
            $receiver = substr($receiver, $pmail + 1, $kmail - $pmail - 1);
        }
        else
        {
            $rfull = $receiver;
        }

        // gradimo redak u tablici
        $p->sender = $sender;
        $p->receiver = $receiver;
        $p->sender_fullname = $sfull;
        $p->receiver_fullname = $rfull;
        $p->google_id = $message->id;
        $p->subject = $subject;
        $p->account = $moja_adresa;
        $p->category = $category;
        $p->fav = false;
        $p->tstamp = $tsmp;

        // nekad su poruke pohranjene u payloadu (ako su dovoljno male)
        $pokusaj1 = base64_decode($message->getPayload()->getBody()->getData());
        // a nekad u Gmail_Message_Part
        $pokusaj2 = $message->getPayload()->getParts();

        // u potonjem slučaju spajamo sve dijelove
        $poruka = '';
        foreach ($pokusaj2 as $tp) {
            //echo '>'.$tp->getMimeType()."<";
            if ('text/plain' == $tp->getMimeType())
                $poruka = base64_decode($tp->getBody()->getData());
        }

        // na kraju odabiremo pravu verziju testiranjem, jer api nema flaga
        if (strlen($pokusaj1) > strlen($poruka))
            $poruka = $pokusaj1;

        // pohranjujemo mail
        $p->content = str_replace("\n", "<br />", $poruka);;
        $p->save();
    }


    /*
     * preuzmi_poruke
     *
     * Pomoćna funkcija za brzo pohranjivanje niza mail objekata u bazu podataka
     *
     * @param $service
     * @param $client
     * @param $moja_adresa e-mail string
     * @param $category za buduću eventualnu organizaciju (sad je uvijek 'inbox', ne uključuje samo chat poruke)
     * @param $idevi polje google-id stringova
     *
     */

    private function preuzmi_poruke($service, $client, $moja_adresa, $category, $idevi)
    {
        // koristimo batch radi bržeg dohvaćanja većeg broja poruka
        $client->setUseBatch(true);
        $batch = new Google_Http_Batch($client);

        foreach (array_reverse($idevi) as $id)
        {
            $req = $service->users_messages->get('me', $id);
            $batch->add($req, $id);
        }

        $results = $batch->execute();
        $client->setUseBatch(false);

        foreach ($results as $result)
        {
            $this->pohrani_poruku($service, $moja_adresa, $category, $result);
        }
    }

    /*
       * refresh_db
       *
       * Pomoćna funkcija za pohranjivanje mail objekta u bazu podataka
       *
       * @param $client
       * @param $moja_adresa e-mail string
       * @param $category za buduću eventualnu organizaciju (sad je uvijek 'inbox', ne uključuje samo chat poruke)
       * @param $idevi polje google-id stringova
       *
       */
    private function refresh_db($service, $client, $moja_adresa, $category)
    {
        // dohvaćamo samo prvih 50 poruka pri prvom spajanju
        $limitirano = false;
        $limit = 0;

        if (Email::where('account', 'LIKE', '%' . $moja_adresa . '%')->count() == 0) {
            $limitirano = true;
            $limit = 50;
        }

        // nastavljamo dok ima novih poruka
        $nastavi = true;
        $stranica = 0;

        // ne spremamo mailove odmah, već ćemo kasnije napraviti batch request (v. pomoćne funkcije iznad)
        $idevi = array();

        while ($nastavi) {

            // biramo sve poruke osim chat poruka
            $params = array('q' => '-in:chats', 'maxResults' => (string)10);
            if ($stranica != 0)
                $params['pageToken'] = $stranica;

            $odg = $service->users_messages->listUsersMessages('me', $params);
            $msgList = $odg->getMessages();

            foreach ($msgList as $msg) {
                // if ($msg->id)

                if (Email::where('google_id', '=', $msg->id)->count() > 0) {
                    $nastavi = false;
                    break; // vec imamo
                }

                $idevi[] = $msg->id;

                if ($limitirano)
                {
                    --$limit;
                    if (!$limit)
                    {
                        $nastavi = false;
                        break;
                    }
                }
            }

            if ($nastavi == false) // pozvan vec break
                break;

            $nastavi = false;
            $stranica = $odg->nextPageToken;
            if ($stranica)
                $nastavi = true;
        }

        $this->preuzmi_poruke($service, $client, $moja_adresa, $category, $idevi);
    }

    var $service;
    var $srv2;
    var $tok;
    var $client;

    /*
      * prepare_google
      *
      * Pomoćna funkcija za incijalizaciju API-a, dobivanje osnovnih informacija i osvježavanje baze
      *
      * @return string, e-mail adresa vlasnika accounta
      *
      */
    public function prepare_google()
    {
        // pripremamo api, te identifikacijske objekte
        // također tražimo adresu trenutnog korisnika (ona identificira korisnika u bazi)
        $this->service = Googlavel::getService('Gmail');

        $this->srv2 = Googlavel::getService('Oauth2');
        $this->tok = Googlavel::getToken();
        $this->client = Googlavel::getClient();

        $this->tok = json_decode($this->tok)->access_token;
        try {
            $adresa = $this->srv2->tokeninfo(['access_token' => $this->tok])->getEmail();
        } catch (Google_Auth_Exception $e){
            Notification::error("Google API token expired. Please log in again.");
            return Redirect::to('/');
        }

        // osvježavamo popis mailova
        $this->refresh_db($this->service, $this->client, $adresa, "inbox");

        return $adresa;
    }

    /**
     * Inbox
     *
     * Osnovna ruta, prikazuje popis primljenih mailova.
     */
    public function inbox()
    {
        $adresa = $this->prepare_google();

        $mailovi = Email::where('receiver', 'LIKE', '%' . $adresa . '%')->where('account', $adresa)->orderBy('tstamp', 'DESC')->get();

        View::share('username', $adresa);
        // koristimo isti view za sve mape, pa ovako biramo ime mape koje će se prikazati:
        View::share('viewing', 'inbox');
        $this->layout->content = View::make('home.inbox')->with('mailovi', $mailovi);

    }

    /**
     * Inbox
     *
     * Osnovna ruta, prikazuje popis poslanih mailova.
     */
    public function outbox()
    {
        $adresa = $this->prepare_google();

        $mailovi = Email::where('sender', 'LIKE', '%' . $adresa . '%')->where('account', $adresa)->orderBy('tstamp', 'DESC')->get();

        View::share('username', $adresa);
        View::share('viewing', 'outbox');
        $this->layout->content = View::make('home.inbox')->with('mailovi', $mailovi);
    }

    /**
     * favorites
     *
     * Osnovna ruta, prikazuje popis mailova označenih kao favorit.
     */
    public function favorites()
    {
        $adresa = $this->prepare_google();

        $mailovi = Email::where('fav', true)->where('account', $adresa)->orderBy('tstamp', 'DESC')->get();

        View::share('username', $adresa);
        View::share('viewing', 'favorites');
        $this->layout->content = View::make('home.inbox')->with('mailovi', $mailovi);
    }

    /**
     * sendmail
     *
     * Ova se funkcija poziva kad korisnik preko GUI forme klikne na slanje maila.
     */
    public function sendmail()
    {
        $adresa = $this->prepare_google();

        // dd(Input::get('To'));
        $this->posalji_poruku($this->service, $adresa,
            Input::get('To'), Input::get('Subject'), Input::get('Message'));

        return Redirect::action("HomeController@inbox");
    }

    /**
     * setfavorite
     *
     * Ova se funkcija poziva kad korisnik preko GUI popisa mailova klikne na checkbox za favorite.
     */
    public function setfavorite(){

        Notification::infoInstant("Favorited!");

        $id = Input::get('messageId');


        $msg = Email::find($id);
        $msg->fav = !$msg->fav ;
        $msg->save();

        // poziv je AJAX, pa za potrebe debuggiranja javljamo uspjeh
        return Response::json(array('status' => 'success'));
    }
}