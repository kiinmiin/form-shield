# Form-Shield paigaldusjuhend

Süsteemi seadistamiseks ja käivitamiseks puhtas Ubuntu keskkonnas tuleb läbida järgmised sammud.

### Vajalike pakettide paigaldamine

Uuendage süsteemi pakettide nimekirja ja paigaldage PHP koos vajalike laiendustega:
sudo apt update
sudo apt install php-cli php-sqlite3 php-curl php-mbstring php-xml unzip -y

### Composeri ja sõltuvuste paigaldamine

Laadige alla ja paigaldage PHP pakihaldur Composer:
curl -sS https://getcomposer.org | php
sudo mv composer.phar /usr/local/bin/composer

Liikuge projekti kausta ja paigaldage vajalikud välised teegid:
composer require maxmind-db/reader openai-php/client

### Rakenduse käivitamine

Käivitage PHP sisseehitatud arendusserver projekti juurkaustas:
php -S 0.0.0.0:8000

Rakenduse testimiseks avage veebibrauseris aadress:
http://localhost:8000/test_form.php

Administraatori paneeli ja logide vaatamiseks avage aadress:
http://localhost:8000/admin.php
