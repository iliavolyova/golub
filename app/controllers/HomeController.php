<?php

class HomeController extends BaseController {

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
        $service->users_messages->send("me", $m); // me je magicna varijabla, al svejedno treba $moja_adresa
    }

    private function pohrani_poruku($service, $moja_adresa, $category, $message)
    {
        $sender = ''; $sfull = '';
        $receiver = ''; $rfull = '';
        $hdrs = $message->getPayload()->getHeaders();
        $tsmp = 0;
        foreach ($hdrs as $komad) {
            if ($komad->getName() == "From")
                $sender = $komad->getValue();
            if ($komad->getName() == "To")
                $receiver = $komad->getValue();
            if ($komad->getName() == "Subject")
                $subject = $komad->getValue();
            if ($komad->getName() == "Date") {
               // Log::info($komad->getValue());

                $tsmp =  $komad->getValue();
            }
           // Log::info($komad->getName());

        }

        $p = new Email;


        $sender = str_replace('"', '', $sender);
        $receiver = str_replace('"', '', $receiver);

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

        $pokusaj1 = base64_decode($message->getPayload()->getBody()->getData());
        $pokusaj2 = $message->getPayload()->getParts();

        $poruka = '';
        foreach ($pokusaj2 as $tp) {
            //echo '>'.$tp->getMimeType()."<";
            if ('text/plain' == $tp->getMimeType())
                $poruka = base64_decode($tp->getBody()->getData());
        }

        if (strlen($pokusaj1) > strlen($poruka))
            $poruka = $pokusaj1;

        $p->content = str_replace("\n", "<br />", $poruka);;

        $p->save();
    }


    private function preuzmi_poruke($service, $client, $moja_adresa, $category, $idevi)
    {
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

    private function refresh_db($service, $client, $moja_adresa, $category)
    {
        $limitirano = false;
        $limit = 0;

        if (Email::where('account', 'LIKE', '%' . $moja_adresa . '%')->count() == 0) {
            $limitirano = true;
            $limit = 50;
        }

        $nastavi = true;
        $stranica = 0;

        $idevi = array();

        while ($nastavi) {

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

    public function prepare_google()
    {
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

        $this->refresh_db($this->service, $this->client, $adresa, "inbox");

        return $adresa;
    }

    /**
     * Inbox
     */
    public function inbox()
    {
        $adresa = $this->prepare_google();

        $mailovi = Email::where('receiver', 'LIKE', '%' . $adresa . '%')->get();

        View::share('username', $adresa);
        View::share('viewing', 'inbox');
        $this->layout->content = View::make('home.inbox')->with('mailovi', $mailovi);

    }

    public function outbox()
    {
        $adresa = $this->prepare_google();

        $mailovi = Email::where('sender', 'LIKE', '%' . $adresa . '%')->get();

        View::share('username', $adresa);
        View::share('viewing', 'outbox');
        $this->layout->content = View::make('home.inbox')->with('mailovi', $mailovi);
    }

    public function favorites()
    {
        $adresa = $this->prepare_google();

        $mailovi = Email::where('fav', true)->get();

        View::share('username', $adresa);
        View::share('viewing', 'favorites');
        $this->layout->content = View::make('home.inbox')->with('mailovi', $mailovi);
    }

    public function sendmail()
    {
        $adresa = $this->prepare_google();

        // dd(Input::get('To'));
        $this->posalji_poruku($this->service, $adresa,
            Input::get('To'), Input::get('Subject'), Input::get('Message'));

        return Redirect::action("HomeController@inbox");
    }

    public function setfavorite(){

        Notification::infoInstant("Favorited!");

        $id = Input::get('messageId');


        $msg = Email::find($id);
        $msg->fav = !$msg->fav ;
        $msg->save();


        return Response::json(array('status' => 'success'));
    }
}