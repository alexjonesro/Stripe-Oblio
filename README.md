Stripe integration with Oblio.eu API implementation for PHP using webhooks.

Daca vinzi in EU/international ceeaza o companie noua in Oblio cu codul de TVA intracomunitar al companiei. https://www.oblio.eu/intrebari-frecvente/cum-adaug-codul-tva-intracomunitar
Vei folosi datele acelea pentru a putea adauga TVA intracomunitar pe facturi.
*Discutati cu contabilul inainte de a face aceste schimbari.
Seteaza contul pentru noua companie de la 0 cu toate datele.

#Install Oblio API using composer

composer require obliosoftware/oblio-api

#Edit the file:

Change the CIF with the EU VAT Cif. (if you sell to EU/international customers)

Change the email

Change the API key


#add the file to your server

#Create a webhook in stripe add the event:
invoice.paid 

##Add the url of the hosted file as a webhook to Stripe:

https://yourserver.com/stripe-oblio.php
