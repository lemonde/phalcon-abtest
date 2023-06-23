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

## Pré-requis

Pour fonctionner correctement vous devez exposer un service nommé `cache` dans l'injection de dépendance de phalcon.

Ce service doit, au minimum exposer 2 méthodes publiques: `hIncrBy` et `hScan`. Ces méthodes sont natives dans la classe
`\Redis` (voir [`hIncrBy`](https://github.com/phpredis/phpredis#hincrby) et [`hScan`](https://github.com/phpredis/phpredis#hscan)).
En utilisant la classe `\Phalcon\Cache\Backend\Redis` vous pouvez les définir comme suit : 

```php
namespace App;

class Redis extends \Phalcon\Cache\Backend\Redis {

    /**
     * @param string $key
     * @param string $hashKey
     * @param int $value
     * @return int
     */
    public function hIncrBy($key, $hashKey, $value)
    {
        return $this->_redis->hIncrBy($key, $hashKey, $value);
    }
    
    /**
     * @param string $key
     * @param string $pattern
     * @param int $count
     * @return array
     */
    public function hScan($key, $pattern = null, $count = 0)
    {
        $iterator = null;
        $results = [];
        $this->_redis->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);
    
        do {
            $arr_keys = $this->_getRedis()->hScan($key, $iterator, $pattern, $count);
    
            if (!$arr_keys) {
                break;
            }
    
            foreach ($arr_keys as $str_field => $str_value) {
                $results[$str_field] = $str_value;
            }
        } while ($arr_keys);
    
        return $results;
    }
}
```

## Installation

Versions :
- 1.X : Phalcon 3
- 2.X : Phalcon 4
- 3.X : Phalcon 5

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
      $router->add('/_my_ab_redirection/{testName:[a-zA-Z0-9\_]+}/{winner:[a-zA-Z0-9\_]+}', ['controller' => 'ab_test', 'action' => 'count', 'namespace' => 'ABTesting\Controller'])->setName('ab_test_redirect');
      ```
      
    4. **(Optionnel)** Ajouter le reporting au routing:
       
          ```php
          $router->add('/_my_ab_dashboard', ['controller' => 'ab_test', 'action' => 'report', 'namespace' => 'ABTesting\Controller'])->setName('ab_test_report');
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
                'chooser' => [\ABTesting\Chooser\PercentChooser::class]
            ],
            'home_link_url' => [
                'default' => 'https://www.google.com',
                'variants' => [
                    'home_test_A' => 'https://www.google.fr',
                    'home_test_B' => 'https://www.google.be',
                ],
                'chooser' => [\ABTesting\Chooser\PercentChooser::class]
            ],
            'home_partial' => [
                'default' => 'path/to/default',
                'variants' => [
                    'home_test_A' => 'path/to/A',
                    'home_test_B' => 'path/to/B',
                ],
                'chooser' => [\ABTesting\Chooser\PercentChooser::class]
            ],
        ],
    
    ]);
    ```
   
    Plus d'info [ici](#configuration-des-tests-ab)
    
4. Déclarer les actions soumises aux tests A/B avec l'annotation `@AbTesting('home_text_content')`

5. Utiliser les fonctions volt pour afficher les élements souhaités, par exemple :
   - pour tester un wording :
   
      ```twig
      <a {{ ab_test_href('home_text_content', 'https://www.google.com') }}>
          {{ ab_test_result('home_text_content') }}
      </a>
      ```
   - pour tester un lien défini comme test :
   
      ```twig
      <a {{ ab_test_href('home_link_url', ab_test_result('home_link_url')) }}>
          Lien
      </a>
      ```
   - pour tester 2 formats :
   
      ```twig
      {# home.volt #}
      
      {{ partial('path/to/specific/partial/dir/' ~ ab_test_result('home_partial')) }}
      ```
   
      ```twig
      {# path/to/specific/partial/dir/path/to/A.volt #}
      
      <!-- your content -->
      <a {{ ab_test_href('home_partial', 'https://example.org/link/for/A') }}>
          Lien
      </a>
      ```
   
      ```twig
      {# path/to/specific/partial/dir/path/to/B.volt #}
      
      <!-- your content -->
      <a {{ ab_test_href('home_partial', 'https://example.org/link/for/B') }}>
          Lien
      </a>
      ```
   
      ```twig
      {# path/to/specific/partial/dir/path/to/default.volt #}
      
      <!-- your content -->
      <a href="https://example.org">
          Lien
      </a>
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
- Le chooser doit être une classe qui implémente `ABTesting\Chooser\ChooserInterface`
- Vous pouvez écouter sur les évenements de l' `ABTesting\Engine` :
  - `abtest:beforeBattle`: avant le calcul d'un test
  - `abtest:afterBattle`: après le calcul d'un test
  - `abtest:beforePrint`: avant l'affichage du résultat via volt
  - `abtest:beforeClick`: avant la redirection via le lien du test
