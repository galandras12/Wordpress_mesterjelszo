=== Mesterjelszó ===
Contributors: galandras12
Tags: password protection, security, maintenance mode, coming soon, access control
Requires at least: 6.4
Tested up to: 7.0.1
Requires PHP: 8.0
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Védd egyetlen mesterjelszóval a teljes weboldalt: oldalak, bejegyzések, egyedi tartalomtípusok, a REST API és a bejelentkezési felület.

== Leírás ==

A **Mesterjelszó** bővítmény egyetlen, admin által beállított jelszóval zárolja a teljes weboldalt a nyilvánosság elől. Amíg a látogató nem adja meg a helyes jelszót, semmilyen weboldal-tartalomhoz (oldalak, bejegyzések, egyedi tartalomtípusok), a WordPress REST API-hoz vagy a bejelentkezési felülethez nem fér hozzá.

**Főbb funkciók**

* Modern, reszponzív, kártya-alapú admin felület élő előnézettel
* Biztonságosan, egyirányú hash formában tárolt mesterjelszó (a WordPress core jelszó-kezelési sztenderdje szerint)
* A jelenlegi mesterjelszó admin jogosultsággal megtekinthető, hogy más adminisztrátorok is nyomon tudják követni
* Opcionális "Emlékezz rám" funkció a látogatóknak, hosszabb (alapértelmezetten 15 napos) munkamenettel
* Feltölthető logó, automatikusan megjelenő weboldal-név, szabadon szerkeszthető üzenet
* Testreszabható háttér (szín vagy kép), átlátszóság, világos / sötét / automatikus megjelenési mód, egyedi színek
* Középre igazított, nagy betűs, akadálymentes (accessible) jelszókérő felület - mobilon és asztali gépen egyaránt
* Brute-force védelem: próbálkozás-limit és ideiglenes zárolás
* Biztonságos, HttpOnly, SameSite sütire épülő munkamenet-kezelés
* Testreszabható REST API kivétel-lista (Jetpack és hasonló, saját hitelesítéssel rendelkező szolgáltatásokhoz); az XML-RPC (xmlrpc.php) mindig automatikusan mentesül
* Nonce-védelem, bemenet-validáció, kimenet-escaping minden felületen
* GDPR-barát: a látogatók IP-címét soha nem tárolja olvasható formában, kizárólag anonimizált hash formájában, automatikusan lejáró adatokban
* Fordításra előkészített (translation-ready) szövegek

**Ismert korlátozás**

A WordPress a feltöltött médiafájlokat (pl. `wp-content/uploads` mappában lévő képeket) alapesetben közvetlenül, a webszerveren (Apache/Nginx) keresztül szolgálja ki, PHP-n kívül. Emiatt egy tisztán PHP-alapú bővítmény - beleértve ezt is - nem tudja garantáltan megakadályozni egy pontosan ismert, közvetlen fájl-URL elérését. A bővítmény minden, a WordPress-en keresztül (oldalak, bejegyzések, mellékletoldalak, REST API) kiszolgált tartalmat véd; a teljes, webszerver-szintű fájlvédelemhez kiegészítő szerverkonfiguráció (pl. .htaccess vagy Nginx szabály) szükséges lehet.

== Telepítés ==

1. Töltsd fel a `mesterjelszo` mappát a `/wp-content/plugins/` könyvtárba, vagy telepítsd a ZIP fájlt közvetlenül a WordPress admin felületén (Bővítmények → Új hozzáadása → Bővítmény feltöltése).
2. Aktiváld a bővítményt a "Bővítmények" menüpontban.
3. Navigálj a "Mesterjelszó" admin menüponthoz, állítsd be a mesterjelszót, majd mentsd el a beállításokat.
4. Kapcsold be a védelmet az "Alapbeállítások" fülön.

== GYIK ==

= Kizárhatom saját magam a weboldalamról? =

Nem: a bejelentkezett, adminisztrátori jogosultsággal rendelkező felhasználók számára a wp-admin irányítópult mindig elérhető marad, függetlenül a beállításoktól, így mindig vissza tudod kapcsolni vagy módosítani a jelszót.

= Hol tárolódik a mesterjelszó? =

A jelszó soha nem kerül olvasható (plaintext) formában tárolásra. A WordPress core saját, jelszavakhoz használt egyirányú hash-elési függvényét (`wp_hash_password()`) használjuk, ugyanazt a sztenderdet, amit a WordPress a felhasználói fiókok jelszavainál is alkalmaz.

= Mi történik, ha elfelejtem a mesterjelszót? =

Bejelentkezett adminisztrátorként a wp-admin irányítópulton keresztül bármikor beállíthatsz egy új mesterjelszót.

= Miért látom a mesterjelszót nyílt szövegben az admin felületen? =

A WordPress core felhasználói jelszavaitól eltérően a mesterjelszó egy megosztott, csapatszinten ismert belépési kód, amit gyakran szükséges visszakeresni és megosztani kollégákkal vagy ügyfelekkel. Ezért - a biztonságos, hash-elt formában tárolt, tényleges hitelesítéshez használt verzió MELLETT - egy titkosított, csak adminisztrátorok által, nonce-védett kérésen keresztül lekérdezhető másolat is elmentésre kerül. A látogatói jelszó-ellenőrzés ettől függetlenül továbbra is kizárólag a hash-elt formát használja.

= Miért nem működik a Jetpack (vagy más, REST API-ra/XML-RPC-re támaszkodó szolgáltatás) bekapcsolt védelem mellett? =

Az xmlrpc.php végpont mindig automatikusan mentesül a zárolás alól. A REST API-hoz emellett a Biztonság fülön megadható egy kivétel-lista (alapértelmezetten a Jetpack szükséges route-jaival feltöltve), amely szintén mindig elérhető marad, függetlenül a jelszóvédelemtől.

== Changelog ==

= 1.0.1 =
* Új: a mesterjelszó admin felületen megtekinthető, hogy más adminisztrátorok is nyomon tudják követni.
* Új: opcionális "Emlékezz rám" funkció, alapértelmezetten 15 napos munkamenettel (alapból kikapcsolva).
* Új: testreszabható REST API kivétel-lista és automatikus XML-RPC mentesítés a Jetpack és hasonló szolgáltatások kompatibilitásához.
* Frissítve: szerzői adatok és plugin URI.
* Megerősítve: WordPress 7.0.1-gyel tesztelve, hibamentesen működik.
* Részletekért lásd a changelog.txt fájlt.

= 1.0.0 =
* Első kiadás.
