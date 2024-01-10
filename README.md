Stripe integration with Oblio.eu API implementation for PHP

#Install Oblio API using composer

composer require obliosoftware/oblio-api

#Edit the file:

Change the CIF

Change the email

Change the API key


#add the file to your server

#Create a webhook in stripe add the event:
invoice.paid 

##Add the url of the hosted file as a webhook to Stripe:

https://yourserver.com/stripe-oblio.php
