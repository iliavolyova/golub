<?php

class HomeController extends BaseController {

    private function send_mail($service, $moja_adresa, $primatelj, $subject, $msg)
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

    private function pohrani_poruku($service, $moja_adresa, $message)
    {
        $sender = '';
        $receiver = '';
        $hdrs = $message->getPayload()->getHeaders();
        foreach ($hdrs as $komad) {
            if ($komad->getName() == "From")
                $sender = $komad->getValue();
            if ($komad->getName() == "To")
                $receiver = $komad->getValue();
            if ($komad->getName() == "Subject")
                $subject = $komad->getValue();
        }

        $p = new Email;

        $p->sender = $sender;
        $p->receiver = $receiver;
        $p->google_id = $message->id;
        $p->subject = $subject;
        $p->account = $moja_adresa;

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


    private function preuzmi_poruke($service, $client, $moja_adresa, $idevi)
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
            $this->pohrani_poruku($service, $moja_adresa, $result);
        }
    }

    private function refresh_db($service, $client, $moja_adresa)
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

        $this->preuzmi_poruke($service, $client, $moja_adresa, $idevi);
    }

    /**
     * Inbox
     */
    public function inbox()
    {
        // Get the google service (related scope must be set)
        $service = Googlavel::getService('Gmail');

        $srv2 = Googlavel::getService('Oauth2');
        $tok = Googlavel::getToken();
        $client = Googlavel::getClient();

        $tok = json_decode($tok)->access_token;
        try {
            $adresa = $srv2->tokeninfo(['access_token' => $tok])->getEmail();
        } catch (Google_Auth_Exception $e){
            Notification::error("Google API token expired. Please log in again.");
            return Redirect::to('/');
        }

        View::share('username', $adresa);

        $this->refresh_db($service, $client, $adresa);

        $mailovi = Email::all();

        $this->layout->content = View::make('home.inbox')->with('mailovi', $mailovi);
    }

    public function outbox()
    {

    }

    public function favorites()
    {

    }

    public function sendmail()
    {
        dd(Input::get('To'));
    }
}