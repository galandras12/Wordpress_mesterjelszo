# 🔐 Mesterjelszó

**Egy WordPress bővítmény, amely egyetlen mesterjelszóval zárja le a teljes weboldalt a nyilvánosság elől.**

[![License: GPLv2](https://img.shields.io/badge/License-GPLv2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
![WordPress](https://img.shields.io/badge/WordPress-6.4+-blue)
![PHP](https://img.shields.io/badge/PHP-8.0+-blue)

---

## 📋 Leírás

A **Mesterjelszó** bővítmény egyetlen, admin által beállított jelszóval zárolt a teljes weboldalt a nyilvánosság elől. Amíg a látogató nem adja meg a helyes jelszót, semmilyen weboldal-tartalomhoz (oldalak, bejegyzések, egyedi tartalomtípusok), a WordPress REST API-hoz vagy a bejelentkezési felülethez nem fér hozzá.

> Tökéletes megoldás **karbantartási módhoz**, **hamarosan nyitás előtti promócióhoz** vagy **teljes oldal-szintű hozzáférés-vezérléshez**.

---

## ✨ Főbb funkciók

- 🎨 **Modern, reszponzív admin felület** – kártya-alapú design élő előnézettel
- 🔒 **Biztonságos jelszó-tárolás** – egyirányú hash (WordPress core sztenderd szerint)
- 🖼️ **Testreszabható felület** – logó, háttérkép/szín, átlátszóság, megjelenési mód
- 📱 **Akadálymentes jelszóképernyő** – teljesen reszponzív, mobil- és asztali-barát
- 🛡️ **Brute-force védelem** – próbálkozás-limit és ideiglenes zárolás
- 🍪 **Biztonságos munkamenet-kezelés** – HttpOnly, SameSite sütik
- 🔐 **Teljes biztonság** – Nonce-védelem, bemenet-validáció, kimenet-escaping
- 🇪🇺 **GDPR-barát** – IP-címek csak anonimizált hash formában, automatikus adatlejárás
- 🌍 **Fordításra előkészített** – könnyen lokalizálható

---

## ⚙️ Technikai adatok

| Követelmény | Érték |
|---|---|
| Minimális WordPress verzió | 6.4 |
| Tesztelt felfelé | 7.0 |
| Szükséges PHP verzió | 8.0+ |
| License | GPLv2 or later |
| Nyelv | PHP (68%), CSS (18%), JavaScript (14%) |

---

## 📦 Telepítés

### 1. Az oszlop formában

```
1. Töltsd fel a `mesterjelszo` mappát a `/wp-content/plugins/` könyvtárba
   VAGY
   Telepítsd a ZIP fájlt közvetlenül a WordPress adminban (Bővítmények → Új hozzáadása → Bővítmény feltöltése)

2. Aktiváld a bővítményt a "Bővítmények" menüpontban

3. Navigálj a "Mesterjelszó" admin menüponthoz:
   - Állítsd be a mesterjelszót
   - Mentsd el a beállításokat

4. Kapcsold be a védelmet az "Alapbeállítások" fülön
```

---

## ❓ Gyakran Ismételt Kérdések

### 🤔 Kizárhatom saját magam a weboldalamról?

**Nem.** Bejelentkezett adminisztrátori jogosultsággal rendelkező felhasználók számára a wp-admin irányítópult mindig elérhető marad, így mindig végig tudod kormányozni az oldalt.

### 🔑 Hol tárolódik a mesterjelszó?

A jelszó **soha nem kerül olvasható (plaintext) formában** tárolásra. A WordPress core saját, jelszavakhoz használt egyirányú hash-elési függvényét (`wp_hash_password()`) használjuk, ugyanúgy, mint a felhasználói jelszavakhoz.

### 😱 Mi történik, ha elfelejtem a mesterjelszót?

Bejelentkezett adminisztrátorként a wp-admin irányítópulton keresztül bármikor beállíthatsz egy új mesterjelszót.

### ⚠️ Ismert korlátozás

A WordPress a feltöltött médiafájlokat (pl. `wp-content/uploads` mappában lévő képeket) alapesetben közvetlenül, a webszerveren (Apache/Nginx) keresztül szolgálja ki, PHP-n kívül. Ez azt jelenti, hogy a **médiafájlok nem védelmezhetők** a Mesterjelszó bővítménnyel. Ha ez probléma számodra, fontold meg egy olyan szerver-szintű konfigurációt (pl. `.htaccess` vagy Nginx rules), amely a `/uploads` mappát is védi.

---

## 📝 Changelog

### 1.0.0
- Első kiadás

---

## 📄 License

Ez a projekt a [GPLv2 vagy újabb](https://www.gnu.org/licenses/gpl-2.0.html) licenc alatt érhető el.

---

## 🤝 Közreműködés

Javaslataid, hibajelentéseid és pull requestjeid mindig szívesen látottak! 

---

**Készítő:** mesterjelszo  
**Utolsó frissítés:** 2026
