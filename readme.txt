=== Mesterjelszó ===
Contributors: mesterjelszo
Tags: password protection, security, maintenance mode, coming soon, access control, REST API, remember me
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Védd egyetlen mesterjelszóval a teljes weboldalt: oldalak, bejegyzések, egyedi tartalomtípusok, a REST API és a bejelentkezési felület zárolása. Szelektív REST API engedélyezés Jetpack és más szolgáltatások számára. "Jegyezz meg" funkció hosszabb munkamenethez.

== Leírás ==

A **Mesterjelszó** bővítmény egyetlen, admin által beállított jelszóval zárolja a teljes weboldalt a nyilvános elől. Amíg a látogató nem adja meg a helyes jelszót, semmilyen weboldalhoz tartalom (oldalak, bejegyzések, egyedi tartalomtípusok), a WordPress REST API-hoz vagy a bejelentkezési felülethez nem fér hozzá.

**Főbb funkciók (1.0.1)**

* Modern, reszponzív, kártya-alapú admin felület élő előnézettel
* Biztonságosan, egyirányú hash formában tárolt mesterjelszó (a WordPress core jelszó-kezelési sztenderdje szerint)
* **Jelszó megjelenítés admin panelen** - Admin számára látható jelszó megjelenítés toggle gombbal és copy funktcióval
* Feltölthető logó, automatikusan megjelenő weboldalnév, szabadon szerkeszthető üzenet
* Testreszabható háttér (szín vagy kép), átlátszóság, világos / sötét / automatikus megjelenési mód, egyedi színok
* Középre igazított, nagy betüs, akadálymentesített (accessible) jelszókérő felület - mobilon és asztali gépen egyaránt
* **"Jegyezz meg" funkció** - Opcionális hosszabb munkamenet (alapbén 15 nap, admin által konfigurálható)
  - Be/ki kapcsolható admin panelen
  - Alapbén kikapcsolt a felhasználói felületen
  - 24 órás alapmunkamenet, ha kikapcsolt
* **Szelektív REST API engedélyezés** - Jetpack és más szolgáltatások számára
  - Admin panelen ki/be kapcsolható
  - Engedélyezett végpontok: `/wp-json/jetpack/*`
  - Bérmilyen más végpont blokkolva marad a védelem alatt
* Brute-force védelem: próbálkozás-limit és ideiglenes zárolás
* Biztonságos, HttpOnly, SameSite sütire épülő munkamenet-kezelés
* Nonce-védelem, bemenet-validáció, kimenet-escaping minden felületen
* GDPR-barát: a látogatók IP-címét soha nem tárolja olvashó formában, kizárólag anonimizált hash formájában, automatikusan lejáró adatokban
* Fordításra előkészített (translation-ready) szövegek
* Magyar és angol nyelvi csomagok tartalmazzák

**Ismert korlátozás**

A WordPress a feltöltött médiafájlokat (pl. `wp-content/uploads` mappában lévő képeket) alapesetben közvetlenül, a webszerveren (Apache/Nginx) keresztül szolgáltatja ki, PHP-n kívül. Ez azt jelenti, hogy a feltöltött médiafájlok a védelem alatt is hozzáférhetőek lehetnek. Ha ezt azért meg szeretned akadályozni, részletező Apache/Nginx konfigurációra lenne szükséged. A bővítmény azonban nem kezeli automatikusan ezt a részt.

== Telepítés ==

1. Töltsd fel a `mesterjelszo` mappát a `/wp-content/plugins/` könyvtárba, vagy telepítsd a ZIP fájlt közvetlenül a WordPress admin felületen (Bővítmények → Új hozzáadása → Bővítmény feltöltése)
2. Aktiváld a bővítményt a "Bővítmények" menüpontban.
3. Navigálj a "Mesterjelszó" admin menüponthoz, állítsd be a mesterjelszót, majd mentsd el a beállításokat.
4. Kapcsold be a védelmet az "Alapbeállítások" fülőn.
5. (Opcionális) Írd be a mesterjelszó megjelenítését a "Biztonság" fülőn (csak admin látja)
6. (Opcionális) Engedélyezd a "Jegyezz meg" gombot a "Felhasználói felület" fülőn
7. (Opcionális) Engedélyezd a szelektív REST API hozzáférést a "Kultüra" fülőn (Jetpack stb.)

== GYIK ==

= Kizárhatom magam a weboldalamról? =

Nem: a bejelentkezett, adminisztrátori jogosultsággal rendelkező felhasználók számára a wp-admin irányítópult mindig elérhető marad, függetlenül a beállításoktól, így mindig viszély van rá hozzáférni.

= Hol tárolódik a mesterjelszó? =

A jelszó soha nem kerül olvashó (plaintext) formában tárolásra. A WordPress core saját, jelszavakhoz használt egyirányú hash-elési függvényét (`wp_hash_password()`) használjuk, ugyanaz, amit a WordPress a más felhasználói jelszavak tárolására használ. Ez a WordPress által ajánlott, biztonságos módszer.

= Mi történik, ha elfelejtem a mesterjelszót? =

Bejelentkezett adminisztrátorként a wp-admin irányítópulton keresztül bármikor beállíhatsz egy új mesterjelszót.

= Jelszó megjelenítés - ki látja? =

Csak az admin felhasználók látják a mesterjelszó megjelenítés fülőt. A jelszó alapbén elrejtett, a beállításban egy toggle gomb segítségével lehet megjeleníteni vagy elrejteni. Másolható a vágólapra.

= Mi a "Jegyezz meg" funkció? =

Ez az opcionális gomb a jelszókérő felületen jelenik meg (csak ha az admin engedélyezi). Ha a látogató bejelöli, akkor sokkal hosszabb munkamenetet kap (alapbén 15 nap). Ha nincs bejelölve, az alapbén 24 órás munkamenet érvényes.

= A "Jegyezz meg" gomb minősül biztonságinak? =

Egyenértékű biztonsággal bír a szokásos munkameneteknek. Az egyetlen különbség az, hogy hosszabb ideig aktív marad. Az HttpOnly és SameSite sütik által védett, csakúgy, mint a normális munkamenetek.

= Jetpack nem működik a védelemmel - mi a teendő? =

A szelektív REST API engedélyezés funkciót kell bekapcsolnod a "Kultúra" fülön. Ez az `/wp-json/jetpack/*` végpontokat engedélyezi, de más végpontok zárolva maradnak. 

= Jegyezz meg - alapértelmezetten működik? =

Nem. Az admin panelen a "Jegyezz meg" funkció **kikapcsolt alapértelmezetten**. Az admin lehet úgy beállítani, hogy megjelenjen a felhasználók előtt. Ha bekapcsoljuk is, a felhasználó még is választhat (alapértelmezetten nem lesz bejelölve).

== Changelog ==

= 1.0.1 =
* Jelszó megjelenítés admin panelen - csak admin látja
* "Jegyezz meg" funkció 15 napos hosszabb munkamenettel
* Szelektív REST API engedélyezés Jetpack és más szolgáltatások számára
* Admin panelen bővült a beállítások mennyisége

= 1.0.0 =
* Első kiadás.
