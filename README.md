<h1 align="center">
Phalcon AB Test
</h1>

<div align="center">
Une librairie pour faire de l'AB Testing et du comptage de résultats
</div>

---

<div align="center">
  <a href="https://travis-ci.com/lemonde/phalcon-abtest"><img src="https://travis-ci.com/lemonde/phalcon-front.svg?token=2NcAxDUbGQgZBQ4pG4yp&branch=master" /></a>
</div>

## Installation

1. Ajouter la dépendance

   > Il faut rajouter le repository git dans votre configuration Composer
   > ```
   > {
   >    "repositories": [
   >        {
   >            "type": "vcs",
   >            "url": "https://github.com/lemonde/phalcon-abtest.git"
   >        }
   >    ],
   > }
   > ```

   ```
   composer require lemonde/phalcon-abtest
   ```

2. Ajouter les configurations PHP

   1. Ajouter le listener des évènements `dispatch`:
      
      ```php
      $eventManager->attach('dispatch', new \ABTesting\Plugin\AnnotationListener());
      ```
   
   2. Ajouter l'extension volt:
   
      ```php
      $volt->getCompiler()->addExtension(new \ABTesting\Volt\ABTestingExtension());
      ```
   
   3. Ajouter le contrôleur au routing:
   
      ```php
      # il faut forcément un paramètre nommé testName
      # et un autre nommé winner
      $router->add('/_rab/{testName:[a-zA-Z0-9\_]+}/{winner:[a-zA-Z0-9]+}', ['controller' => 'ab_test', 'action' => 'count', 'namespace' => 'ABTesting\Controller'])->setName('ab_test_redirect');
      ```
      
3. Ajouter la configuration des tests A/B (via un service nommé `config` utilisant `\Phalcon\Config`)

    ```php
    $config = new Phalcon\Config([
        
        // ...
        
        'ab_test' => [
            'home_text_content' => [
                'default' => 'home_test_A',
                'variants' => [
                    'home_test_A' => 'something',
                    'home_test_B' => 'some other thing',
                ],
                'chooser' => [\ABTesting\Chooser\RandomChooser::class]
            ]
        ],
    
    ]);
    ```
   
    Plus d'info [ici](#configuration-des-tests-ab)
    
4. Déclarer les actions soumises aux tests A/B avec l'annotation `@AbTesting('home_text_content')`

5. Utiliser les fonctions volt pour afficher les élements souhaités

    ```twig
    <a href="{{ ab_test_click('home_text_content', 'https://www.google.com') }}">{{ ab_test_result('home_text_content') }}</a>
    ```
   
## Configuration des tests A/B

Pour configurer vos tests A/B, tout se fait dans une conf en tableau sous la forme :

```php
'nom_du_test' => [ # Définition du test
    'variants' => [ # Définition des résultats possibles
        'varianteA' => 'une valeur',
        'varianteB' => 'une autre valeur',
        'varianteC' => 1337,
    ],
    'default' => 'valeur par défaut', # Valeur par défaut du résultat (s'il n'y a pas eu de bataille par exemple)
    'chooser' => ['\La\Classe\Du\Chooser', 'les', 'arguments', 'du', 'constructeur']
]
```

- Les variantes peuvent être de n'importe quel type compatible avec `var_export`. 
- Le chooser doit être une classe qui étend `ABTesting\Chooser\AbstractChooser`
- Vous pouvez écouter sur les évenements de l' `ABTesting\Engine` :
  - `abtest:beforeBattle`: avant le calcul d'un test
  - `abtest:afterBattle`: après le calcul d'un test
  - `abtest:beforePrint`: avant l'affichage du résultat via volt
  - `abtest:beforeClick`: avant la redirection via le lien du test
