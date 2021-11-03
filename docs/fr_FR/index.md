# Plugin mcztherm

Ce plugin permet de créer et gérer des thermostats simples pour piloter le chauffage d'un poêle à pellets de la gamme MCZ Maestro.

Ses principales fonctionnalités sont:
   -  module le niveau de chauffe en fonction de la différence entre la température de consigne et la température ambiante
   -  gére deux mode de chauffe: Jour et Nuit
   -  dispose d'un mode **hystérésis**
   -  utilise le module python pour dialoguer avec un poêle MCZ Maestro via MQTT
   -  conçu pour permettre un démarrage différé dans une seconde résidence
   -  permet de synchroniser l'heure du poele avec celle de Jeedom


Le mode **hystérésis** permet de gérer l’allumage et l’extinction du chauffage en fonction de la température intérieure, par rapport à un seuil correspondant à la consigne. L’hystérésis permet d’éviter des séquences arrêts, allumages trop fréquentes lorsque la température est autour la consigne.

# Configuration

Ce plugin est destiné à la création de thermostats dans Jeedom.


## La configuration en quelques clics


![Aspect sur le Dashboard](./images/dashboard.png)

Sur le dashboard, vous avez un bouton pour activer ou stopper le thermostat, un curseur pour spécifier la température de consigne.

Ce bouton vous permet de déroger aux consignes spécifiées dans les modes jour et nuit jusqu'au prochain changement de mode.

Le bouton Activation vous permet de prévoir une activation automatique du thermostat à l'heure indiquée en dessous.

Ceci permet entre autre d'activer, en pleine nuit, le thermostat et le poêle de la seconde résidence afin d'avoir une température agréable à votre arrivée le lendemain.

Ce bouton Activation est automatiquement désactivé après utilisation.

## La création d’un thermostat en détail

Pour créer un nouveau thermostat, rendez-vous sur la page de configuration en déroulant le menu Plugins/Confort et sélectionnez mcztherm. Cliquez sur le bouton *Ajouter* situé en haut à gauche et renseignez le nom souhaité pour votre thermostat.

### La configration générale

![Configuration générale](./images/mcz_config_generale.png)

Dans cette page de configuration, outre les informations habituelles pour un équipement, vous avez la possibilité d'activer ou pas un mode. De lui spécifier sa température dee consigne ainsi que son heure d'activation.

C'est aussi dans cette page que vous configurez la sonde intérieure.

Si vous désirez être alerté en cas d'erreur du poêle, vous pouvez spécifier la commande message à utiliser.

### Les consignes

![Configuration des consignes](./images/consignes.png)

Cette page permet de configurer les consignes de fonctionnement.

Vous avez l'offset des seuils d'activation des différents niveau de puissance ou d'arrêt, la définition de l'hystérèse ainsi que le délai minimum entre l'ordre d'arrêt et un nouvel allumage du poêle.

Pour chaque consigne, vous devez définir les action qui doivent être exécutées.

Par exemple, pour la consigne Puissance 1, j'ai défini chez moi que les actions suivantes doivent être exécutées:
   -  Profil manuel
   -  Mode ECO off
   -  niveau ventilateur ambiance à 1
   -  niveau ventilateur canalisé à 1
   -  Puissance niveau 1

> **Tip**
>
> Il faut définir au niveau de MQTT des commandes utnitaires.  C'est à dire une commande pour un fonction bien précise. 
> 
> Il faudra donc créer une commande par niveau de puissance, une commande par niveau du ventilateur ambaince, une commande par niveau du ventilateur canalisé, ...
>

La valeur d'hystérèse est divisée en deux.  Une demi est ajoutée à la température du seuil en phase de température montante.  En phase de température descendante, un demi est soustrait du seuil.

### Les infos

![Infos du poêle](./images/infos_poele.png)

Dans cet écran, vous avez les commandes à spécifier pour obtenir les informations du poêle et réagir en conséquence.

L'information Valeur état off est le texte donnant l'état du poêle à l'état off. 

La zone Attente sur un état spécifie les textes renvoyés par le poêle pour lesquels il faut attendre. Il est inutile d'envoyer des commandes au poêle durant ces phases.

La dernière zone Consigne de température permet de connaître la consigne de température connue du poêle.

### Les commandes 

![Commandes du poêle](./images/commandes_poele.png)

Dans cet écran, vous allez spécifier les commandes à utiliser pour allumer ou éteindre le poêle.

Il y également la commande pour indiquer la température de consigne au poêle.

La dernière commande permet d'effectuer la mise à jour de la date et l'heure du poêle ainsi que l'heure d'exécution.

> **Attention**
>
> Cette commande nécessite une version modifiée du script python maestro.py.  Le script doit traiter la commande 9001 pour envoyer la commande C|SalvaDataOra|DDMMYYYYHHmm
>


## Logs


