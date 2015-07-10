<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/


Route::get('/login', function()
{
    if ( Input::has('code') )
    {
        $code = Input::get('code');

        // authenticate with Google API
        if ( Googlavel::authenticate($code) )
        {
            return Redirect::to('/protected');
        }
    }

    // get auth url
    $url = Googlavel::authUrl();

    return link_to($url, 'Login with Google!');
});

Route::get('/logout', function()
{
    // perform a logout with redirect
    return Googlavel::logout('/');
});

function send_mail($service, $moja_adresa, $primatelj, $subject, $msg)
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

function refresh_db($service)
{

    $nastavi = true;
    $stranica = 0;

    while ($nastavi) {

        $params = array('q' => '-in:chats', 'maxResults' => '2');

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

            $message = $service->users_messages->get('me', $msg->id);
            $snip = $message->getSnippet();
            //echo "{$snip} <br>";

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
            //echo $sender . " -> " . $receiver;

            $p = new Email;

            $p->sender = $sender;
            $p->receiver = $receiver;
            $p->google_id = $message->id;
            $p->subject = $subject;

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

            //echo $pokusaj1 . " - - " . $poruka . " /// ";

            $p->content = $poruka;
            //echo "<br>content: " . $p->content;

            $p->save();
        }

        if ($nastavi == false) // pozvan vec break
            break;

        $nastavi = false;
        $stranica = $odg->nextPageToken;
        if ($stranica)
             $nastavi = true;
    }
}

function dump_db()
{
    echo '<table border = "1">'; // da bude ruzno

    $mailovi = Email::all();

    foreach ($mailovi as $mail)
    {
        echo "<tr>";
        echo "<td>$mail->sender</td>";
        echo "<td>$mail->receiver</td>";
        echo "<td>$mail->subject</td>";
        echo "<td>$mail->content</td>";
        echo "</tr>";
    }
    echo "</table>";
}

Route::get('/protected', function()
{
    // Get the google service (related scope must be set)
    $service = Googlavel::getService('Gmail');

    $srv2 = Googlavel::getService('Oauth2');
    $tok = Googlavel::getToken();

    $tok = json_decode($tok)->access_token;
    $adresa = $srv2->tokeninfo(['access_token' => $tok])->getEmail();

    echo "Korisnik: $adresa <br />";

    refresh_db($service);
    dump_db();


    return link_to('/logout', 'Logout');
});

// Homepage
Route::get('/', array('as' => 'home', 'uses' => 'HomeController@index'));
// Errors
Route::get('404', array('as' => '404', 'uses' => 'ErrorController@get404'));