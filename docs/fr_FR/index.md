# Description

Ce plugin permet de faire le lien entre votre Jeedom et Alexa

Il peut fonctionner en passant par un service cloud mis à disposition par Jeedom

# Mise en place de la connexion vers Alexa

> **IMPORTANT**
>
> Il est important de faire les étapes suivantes dans l'ordre indiqué !!!

## Configuration Market

<<<<<<< HEAD
Après l'installation du plugin, il vous suffit d'aller dans la partie configuration du plugin puis de cliquer sur envoyer la configuration au market puis d'attendre 24h.
=======
Après l'installation du plugin, il vous suffit d'aller dans la partie Administration de Jeedom, puis la partie API et de récupérer la clef API du plugin Google Smarthome.

Ensuite sur le market dans votre profil partie "Mes Jeedoms", il faut que vous complétiez les champs "Google Smarthome" : 

- Activez le service google Smarthome
- Donnez l'url de votre Jeedom (HTTPS obligatoire sinon votre configuration ne passera jamais active)
- Mettez la clef API précédemment récuperée

Validez ensuite la configuration. 
>>>>>>> f7bb493f7885436903765b7feef9aa888ecbc177

Il vous faut maintenant attendre 24h le temps que votre demande soit prise en compte.

> **IMPORTANT**
>
> Suite à l'activation et/ou modification des informations pour Alexa il faut attendre 24h pour que cela soit prise en compte

## Configuration du plugin

Sur votre Jeedom, allez sur Plugin -> Communication -> Alexa et dans la partie équipement sélectionnez les équipements à transmettre à Alexa ainsi que le type de l'équipement.

> **IMPORTANT**
>
> Le plugin se base sur les types génériques de Jeedom des commandes pour piloter votre domotique. Il est donc très important de configurer ceux-ci correctement.

Vous pouvez aussi créer des scènes dans l'onglet scène, avec des actions d'entrée et de sortie.

<<<<<<< HEAD
![ash](../images/ash3.png)
=======
## Configuration Google

Depuis votre téléphone, lancez l'application Google Home puis dans le Menu (à gauche) cliquez sur "Contrôle de maison".

Cliquez sur le "+" et cherchez "Jeedom Smarthome" dans la liste.

Une fois sélectionné indiquez vos identifiants Market.

L'application va normalement se synchroniser avec vos équipements, vous pourrez ensuite les mettres dans les pièces et piloter vocalement votre domotique.
>>>>>>> f7bb493f7885436903765b7feef9aa888ecbc177

# FAQ

>**Lors de la connexion j'ai eu page blanche avec du texte bizarre ?**
>
<<<<<<< HEAD
>Votre mot de passe ou nom d'utilisateur n'est pas reconnu. Avez vous bien activer Alexa (Amazon smarthome) sur le market ? Avez vous bien mis une URL ? Avez vous bien mis une clef API pour Alexa ? Avez vous bien attendu 24h suite à cela ? Mettez vous bien vos identifiants market ?
=======
>Votre mot de passe ou nom d'utilisateur n'est pas reconnu. Avez vous bien activer Google Smarthome sur le market ? Avez vous bien mis une URL ? Avez vous bien mis une clef API pour Google Smarthome ? Avez vous bien attendu 24h suite à cela ? Mettez vous bien vos identifiants market ?

>**Quelles sont les commandes possibles ?**
>
>Les commandes vocales (ainsi que les retours) sont gérés uniquement pas Google, voila la [documentation](https://support.google.com/googlehome/answer/7073578?hl=fr)

>**L'assistant me demande d'affecter les pieces mais je ne sais pas à quoi correspond l'équipement.**
>
>Oui l'assisant n'affiche pas le nom réel de l'équipement, juste le pseudo. Il faut donc quitter l'assistant et revenir sur la page d'acceuil du controle de la maison. La en cliquant sur l'équipement vous allez avoir son nom vous pourrez ensuite l'affecter à une piece

>**Pourquoi faut-il affecter des pièces à chaque équipement ?**
>
>Car Google ne permet pas de le faire par l'API vous devez donc absolument le faire manuellement.

>**Pourquoi faut-il attendre 24h pour que l'activation de Google Smarthome soit en place ?**
>
>Car nous avons dédié un serveur juste pour faire le pont avec votre Jeedom et Google Home, et celui-ci ne se synchronise que toutes les 24h avec le market.

>**Google ne fait pas toujours ce que je dis**
>
> Effectivement lors de nos tests nous avons rencontré des soucis assez gênant : les pluriels et genre.
> Exemple : "Ok google ferme tous les volets" ne marchera pas à cause du s à volet alors que "Ok google ferme volet" marchera.

>**Comment faire pour resynchroniser les équipements avec Google**
>
> En lisant simplement le message lors d'une sauvegarde sur la page du plugin, il vous indiquera comment faire.
>>>>>>> f7bb493f7885436903765b7feef9aa888ecbc177
