IpernityBackup
==============

PHP Ipernity backup script


---------------------------

Script PHP pour sauvegarder un compte Ipernity
Télécharge les documents et les données json (liste documents, albums et tags)

Utilisation:

- téléchargez le fichier IpenityBackup.php ( wget https://raw.github.com/damienlabat/IpernityBackup/master/IpernityBackup.php )
- ouvrez le et renseignez APIKEY et APISECRET ( demandez une clef ici http://www.ipernity.com/apps/key )
- executez le script
	php IpenityBackup.php
	et saisissez l'identifiant du compte à sauvegarder
	ou directement
	php IpenityBackup.php identifiant_du_compte_a_sauvegarder
- vous trouverez les données sauvegardez dans le dossier 'data'

