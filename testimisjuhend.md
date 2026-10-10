# Form-Shield komponent

Iseseisev PHP-põhine kaitsekiht kontaktivormidele, mis rakendab mitmetasandilist kontrolli enne teadete töötlemist. Lahendus on loodud integreerimiseks olemasolevate vormikäitlejate juurde lisamise (require/include) teel.

### Kaitsekihtide toimimise kirjeldus

* Honeypot: Kontrollib varjatud sisestusvälja olemasolu. Kui robot täidab selle välise välja, märgitakse päring spämmi hulka, kuid sellest ei teavitata kasutajat ja andmeid ei salvestata andmebaasi.
* Rate limit: Piirab ühe IP-aadressi päringute arvu minutis, salvestades andmed SQLite andmebaasi. Piirangu ületamisel blokeeritakse päring koheselt ja veebiserver tagastab kliendile HTTP staatuse 429.
* Deterministlik heuristika: Rakendab staatilisi reegleid, kontrollides teksti pikkust ja liigsete veebiaadresside olemasolu sõnumis.
* Šabloonide tuvastus: Võrdleb sõnumit administraatori poolt määratud keelatud märksõnade loeteluga.
* GeoIP: Tuvastab välise API päringu abil kasutaja riigi.
* Telefoninumbri kontroll: Kontrollib regulaaravaldise abil, kas sisestatud telefoninumber vastab koduriigi formaadile.
* AI klassifikaator: Hindab OpenAI API või simulatsioonimudeli abil sõnumi sisu ja tuvastab tüüpilisi spämmfraase.

### Deterministliku heuristika näited

Reegel: Liiga lühike sõnum
* Tabab: "hi" või "test" (tekst on lühem kui viis sümbolit)
* Ei taba: "Soovin broneerida aega konsultatsiooniks."

Reegel: Liigsed veebiaadressid
* Tabab: "Külastage lehte http://spam1.com ja tellige http://spam2.com" (sisaldab rohkem kui ühte linki)
* Ei taba: "Minu koduleht asub aadressil https://firma.ee"

### Süsteemi testimise juhend

* Puhta päringu kontrollimiseks täitke kontaktivorm korrektsete andmetega, lisades tavalise tekstsõnumi ning Eesti suunakoodiga telefoninumbri. Süsteem peab teate legitiimsena läbi laskma.
* Honeypot kihi testimiseks avage brauseri arendustööriistad ning muutke varjatud div-elemendi stiil nähtavaks. Kirjutage väljale suvaline tekst ja saatke vorm. Süsteem peab teate vaikselt blokeerima.
* Liigsete URL-ide kontrollimiseks sisestage sõnumi lahtrisse mitu veebiaadressi. Rakendus peab sõnumi blokeerima ja logima selle administraatori paneeli.
* Päringute piiramise kontrollimiseks vajutage vormi saatmise nuppu kiiresti mitu korda järjest. Süsteem peab väljastama veateate liigsete päringute kohta.
* Telefoni ja AI kihtide testimiseks sisestage vigane telefoninumber või spämmile viitavad ingliskeelsed ärilised väljendid. Blokeeritud päringud ilmuvad administraatori liidesesse.
* Logide vaatamiseks ja uute šabloonide lisamiseks navigeerige administraatori lehele aadressil /admin.php.
