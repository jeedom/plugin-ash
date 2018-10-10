# Description

Ce plugin permet de faire le lien entre votre Jeedom et Alexa

Il peut fonctionner en passant par un service cloud mis à disposition par Jeedom

# Mise en place de la connexion vers Alexa

> **IMPORTANT**
>
> Il est important de faire les étapes suivantes dans l'ordre indiqué !!!

## Configuration Market

Après l'installation du plugin, il vous suffit d'aller dans la partie configuration du plugin puis de cliquer sur envoyer la configuration au market puis d'attendre 1h. Vous pouvez voir l'état sur le votre page profils sur le market, partie "Mes services" puis configuration sur le service d'assistant vocal (le status Amazon Alexa doit etre sur actif).

> **IMPORTANT**
>
> Si le service ne passe pas en actif au bout de quelques heures verifiez que : l'url est bien en https (c'est obligatoire et disponible gratuitement avec un service pack power ou plus), que la case "Activer Amazon alexa" est bien cochée

> **IMPORTANT**
>
> Suite à l'activation et/ou modification des informations pour Alexa il faut attendre 1h pour que cela soit prise en compte

## Configuration Alexa

Une fois le service bien actif il faut sur l'application Alexa ou sur le site web (https://alexa.amazon.fr/spa/index.html) aller dans la partie skill et chercher le skill Jeedom puis l'activer, la il vous demandera des identifants il faut mettre ceux du market (attention bien mettre vos identifiants market et non ceux de votre Jeedom)

> **IMPORTANT**
>
> Si vous avez un message d'erreur vous indiquants que votre mot de passe ou non d'utilisateur n'est pas bon il faut : 
> - vérifier qu'Amazon Alexa est bien en actif sur le market
> - si c'est bien le cas, etês vous sur de vos identifiants (attention il faut respecter majuscule/minuscule) ? 
> - si c'est le cas changer votre mot de passe sur le market et attendez 1h puis retestez

## Configuration du plugin

Sur votre Jeedom, allez sur Plugin -> Communication -> Alexa et dans la partie équipement sélectionnez les équipements à transmettre à Alexa ainsi que le type de l'équipement.

> **IMPORTANT**
>
> Le plugin se base sur les types génériques de Jeedom des commandes pour piloter votre domotique. Il est donc très important de configurer ceux-ci correctement. Vous pouvez voir [ici](https://jeedom.github.io/plugin-mobile/fr_FR/#tocAnchor-1-6) des explications sur les generiques type

Vous pouvez aussi créer des scènes dans l'onglet scène, avec des actions d'entrée et de sortie.

# FAQ

>**Quand je demande de fermer les volets ça s'ouvre et quand je demande d'ouvrir ca se ferme ?**
>
> Sur les volets ayant un générique type "Volet Bouton Slider" le plugin va utiliser cette commande pour le piloter en demandant un état 100% pour la fermeture et 0% pour l'ouverture le soucis peut donc venir d'un branchement inversé de votre module

>**J'ai lu que le plugin necessiterait un abonnement, pourquoi ?**
>
> C'est assez simple, Jeedom est une solution non-cloud (entendre tout est chez vous il n'y a pas de serveur de notre coté pour votre Jeedom) mais Alexa ne peut envoyer des demandes que vers un serveur unique pour un skill. On a donc été obligé de faire un serveur qui recoit toute les demandes d'Alexa et les retransmets vers votre Jeedom. Malheureusement se serveur (en plus du coût de développement et de maintenance) à un coût de location de notre coté. Voila pourquoi il y aura un abonnement pour le service "Assistant Vocal" qui couvrira et Amazon et Google (vous inquietez pas pour le prix il sera minime, le but est juste d'amortir le coût du serveur)
