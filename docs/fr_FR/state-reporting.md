# Envoi automatique des états à Alexa

L'option **Envoyer les changements d'état à Alexa**, dans la configuration du
plugin, permet de mettre à jour Alexa quand une commande d'information Jeedom
change, sans attendre une interrogation d'Alexa. Elle est désactivée par défaut.

## Activation

1. Mettre en place la version cloud-service compatible avec Alexa Events et les
   autorisations Amazon. La procédure développeur figure dans
   `jeedom/cloud-service`, fichier `docs/alexa-events.md`.
2. Installer cette version ASH et vérifier les types génériques et les commandes
   d'information des équipements transmis.
3. Cocher **Envoyer les changements d'état à Alexa**, puis sauvegarder.
4. Relancer une découverte des équipements dans Alexa pour actualiser leurs
   capacités. Les identifiants existants sont conservés.

Seules les propriétés Alexa lisibles à partir des commandes d'information sont
déclarées comme proactives. Les scènes et les actions sans retour d'état ne
produisent pas de faux états. Le serveur confirme également que l'équipement a
bien été découvert par le compte Amazon destinataire.

## Fonctionnement

Un listener léger capture la valeur et la date de l'événement dans une file locale
sur disque. L'envoi HTTP se fait dans un processus en arrière-plan. Les transitions
rapides, par exemple ouvert puis fermé, sont conservées dans leur ordre ; une
valeur identique répétée ne déclenche pas un nouvel envoi. Les conversions et
horodatages UTC sont partagés avec les réponses aux interrogations d'Alexa.

Les commandes d'action ne sont jamais exécutées pour lire un état. Un changement
capturé pendant le traitement d'un ordre Alexa est marqué `VOICE_INTERACTION`.
Pour les autres événements, dont Jeedom ne fournit pas une origine commune fiable,
la cause utilisée est `PHYSICAL_INTERACTION`. L'origine d'un retour asynchrone
tardif ne peut donc pas toujours être rattachée à l'ordre vocal initial.

La disponibilité `Alexa.EndpointHealth` est jointe aux états et lisible dans les
réponses aux interrogations d'Alexa. Le statut Jeedom `timeout` sert à détecter un
équipement inaccessible. Cette propriété ne déclare pas d'événements proactifs
autonomes : Jeedom ne fournit pas un événement immédiat et fiable pour chaque
changement de disponibilité.

Un échec d'envoi au cloud conserve l'événement et le cron minute relance l'envoi.
Le cloud conserve ensuite sa propre file durable et gère les erreurs Amazon ainsi
que le renouvellement des jetons. Les files locales sont limitées à 5 000 événements
et les événements de plus de 24 h sont abandonnés. La file contient des états,
aucun jeton Amazon ; ces derniers restent uniquement sur cloud-service.

Le routage respecte l'installation, le compte Market, les comptes multi-box et les
groupes d'équipements. La rotation de la clé API ASH ne change pas l'identifiant
persistant d'installation.

## Diagnostic

- **Aucun état mis à jour** : vérifier l'option, le retour d'état Jeedom, la réception
  des autorisations `AcceptGrant` côté serveur, puis refaire une découverte Alexa.
- **Remontée différée** dans le journal ASH : le serveur n'a pas confirmé la prise
  en charge. La file est conservée et l'envoi sera réessayé.
- Un délai réseau, une panne du cloud ou d'Amazon peut retarder les mises à jour.
  Le fonctionnement habituel des ordres et interrogations reste disponible.

La désactivation de l'option retire les listeners et efface les états locaux en
attente. Pour désactiver également les capacités déjà annoncées à Alexa, refaire
une découverte après la sauvegarde.

Validation locale : `php tests/report-state.php`, sans installation Jeedom ni
accès Amazon. Un essai avec une skill et un compte Amazon de développement reste
nécessaire avant publication.
