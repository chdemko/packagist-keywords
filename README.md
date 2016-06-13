php-packagist-keywords
======================
[![Downloads](https://img.shields.io/packagist/dt/thegalactic/php-packagist-keywords.svg)](https://packagist.org/packages/thegalactic/php-packagist-keywords)
[![License](https://img.shields.io/packagist/l/thegalactic/php-packagist-keywords.svg)](http://www.cecill.info/licences/Licence_CeCILL-B_V1-en.html)

This software has been realized to extract the projects from packagist.org which are linked to the main keywords.

Installation using composer
---------------------------

~~~
composer require thegalactic/php-packagist-keywords:dev-master
~~~

Usage
-----

Example: get data for projects using keywords which are in the 20 most used and which are used by at least 10% of the projects.

~~~
./vendor/thegalactic/php-packagist-keywords/cli/packagist-keywords.sh\
    --output=filename.csv\
    --max=20\
    --ratio=0.10
~~~

* if `output` is not provided, a default filename is produced;
* if `max` is not provided, all keywords are considered;
* if `ratio` is not provided, its default is 0.

