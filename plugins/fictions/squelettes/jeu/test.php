<?php

# enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);


		 $subject = 'Vous venez d\'écrire un chapitre !';
//        $message = "Bonjour,\n\nMerci d’avoir participé au petit fablab d’écriture !\n\nAccédez dès maintenant à votre chapitre en ligne : http://fictions.laclasse.com/spip.php?scenario=jeu&page=lecture&id_rubrique=".$id_rubrique."\n\nUn deuxième message vous préviendra lorsque votre histoire sera disponible.\n\n A très bientôt,\nL’équipe du petit fablab d’écriture\n\nSuivez nos actualités sur Twitter @petitfablab ou sur le blog https://petit-fablab-ecriture.tumblr.com/ \n\nLe petit fablab d'écriture est un dispositif imaginé par Erasme, laboratoire d'innovation ouverte de la Métropole de Lyon, en collaboration avec la Villa Gillet.";

		$headers = 'From: info@erasme.org' . "\r\n" .
		 'Reply-To: no-reply@erasme.org' . "\r\n" .
		 //"Bcc: pvincent@erasme.org, petitfablab@gmail.com" . "\r\n" .
		 "Content-Type: text/plain; charset='utf-8'" . "\r\n" .
		 'X-Mailer: PHP/' . phpversion();

		mail('yassin@siouda.com', $subject, 'hello', $headers);
