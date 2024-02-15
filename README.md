
# Bienvenue dans ce dépôt !


## Installation
Pour installer ce projet, suivez les étapes ci-dessous :

1. Clonez ce dépôt en utilisant la commande suivante :

> git clone https://github.com/[utilisateur]/[nom_du_depot].git

2. Accédez au répertoire du projet :

> cd [nom_du_depot]

3. Installez les dépendances en utilisant la commande suivante :

> composer install

4. Configurez votre base de données en modifiant le fichier .env et en exécutant les commandes suivantes :

> php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate


## Utilisation
Pour utiliser ce projet, vous pouvez lancer le serveur Symfony en utilisant la commande suivante :

> symfony server:start

Ensuite, vous pouvez accéder à l'application en ouvrant votre navigateur à l'adresse ```http://localhost:8000```.

Vous pouvez également utiliser les commandes suivantes pour interagir avec l'application :

* ```php bin/console make:entity``` : Créez une nouvelle entité
* ```php bin/console make:migration``` : Créez une nouvelle migration
* ```php bin/console doctrine:migrations:migrate``` : Exécutez les migrations
* ```php bin/console debug:router``` : Affichez la liste des routes disponibles

## Contribution
Pour contribuer à ce projet, suivez les étapes ci-dessous :

1. Fork ce dépôt.
2. Créez une branche pour votre contribution :
> git checkout -b [nom_de_la_branche]
3. Faites vos modifications.
4. Ajoutez et committez vos modifications :
> ```git add .```
```git commit -m[description_des_modifications]"```

5. Poussez votre branche vers votre dépôt forké :
> ```git push origin [nom_de_la_branche]```

6. Envoyez une pull request.
