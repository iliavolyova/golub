This is a simple Gmail web-facade project built as part of a college course. 
The rest of the provided information is intended to give our instructors a general idea of how it works.

#Golub Mail

GolubMail je jednostavno web sučelje za upravljanje Gmail računima i e-poštom. Korisnici se spajaju direktno preko svojih Google računa,
te sve akcije obavljaju direktno preko Googleovog API-a. 
Uz slanje i primanje elektroničke pošte, moguće je označavati važnije poruke radi lakšeg pristupa.
Aplikacija podržava sučelje lokalizirano na engleski i hrvatski jezik.

S tehničke strane, GolubMail radi na Laravel 4 frameworku uz nekoliko dodatnih biblioteka, te koristi MySQL bazu.

---

##Laravel 4

Laravel je framework koji zajedno sa svojim skupom popratnih biblioteka čini zaokruženu cjelinu za izradu web aplikacija po MVC (Model View Controller) paradigmi.
Dodatne biblioteke se dodaju popratnim alatom Composer, koji ujedno vodi brigu o kompatibilnosti među bibliotekama koje ovise jedne o drugima.
Biblioteke se navedu u datateci composer.json te lako instaliraju naredbom 

    composer update

Biblioteke toga nisu eksplicitno uključene u ovaj git repozitorij, već se preuzimaju navedenom naredbom.

---

##Arhitektura projekta

Svi elementi koji se tiču *izrade* projekta nalaze su direktoriju ***app***. Neki od važnijih poddirektorija za ovaj projekt su: 

 - **config** direktorij sadrži sve konfiguracijske datateke 
 - **controllers** koji sadrži naše kontrolere 
 - **database** koji sadrži migracije i inicijalne podatke za bazu podataka
 - **views** koji sadrži pregledne datoteke (viewove) koje se pune sadržajem i pretvaraju u HTML koji ide klijentu
 
 - osim ovih direktorija imamo 2 važne datoteke u samom **app** direktoriju:
    - *routes.php*: ovdje se čuvaju pozivi funkcija koje zahtjev za određenom rutom proslijeđuju odgovarajućim kontrolerima na obradu
    - *filters.php*: ovdje je moguće definirati *hookove* prije ključnih momenata u radu aplikacije (prije nego je započeta obrada zahtjeva, ili nakon). Primjerice, nakon svakog zahtjeva dodajemo kolačić sa trenutno odabranim jezikom radi javascripta na klijentu.
    
U navedenim direktorijima su ujedno i ključne datoteke koje sadrže gotovo sav kod za ovaj projekt.

---

##Backend 

###Google OAuth i Gmail API
Google OAuth je univerzalni servis za prijavu putem Googleovog računa. GMail API omogućava potpun pristup sadržaju korisnikovog GMail sandučića. Nažalost, ne nudi puno više od toga, pa smo primjerice morali koristiti **PHPMailer** za ispravno formatiranje poruka, u skladu sa standardima e-pošte. 
Kako bismo lakše integrirali Googleovu *low-level* biblioteku za baratanje API-jima, koristili smo biblioteku Googlavel koja pruža sučelje izvedeno kao Laravelov servis prema kontrolerima.

---

### Baza
Laravel nudi sučelja (*bindings*) za nekoliko najpopularnijih baza podataka. S obzirom na to da je naša aplikacija višekorisnička, a i s obzirom na to da RP2 server samo nju podržava, odabrali smo MySQL.

Baza sadrži samo jednu tablicu, *emails*. U njoj se bilježe sljedeći tipovi pošte za svakog korisnika:

 1. Primljena pošta,
 2. Poslana pošta te
 3. Favoriti.

Pritom se zapravo u bazi drže prve dvije kategorije poruka, dok su favoriti označeni bulovskom zastavicom. 

---

### Dohvaćanje poruka
Poštu držimo u bazi prvenstveno zbog brzine. Naime, dohvaćanje poruka preko Googleovog API-a je relativno spor proces. Stoga u inicijalnom korisnikovom spajanju odjednom preuzimamo do 50 poruka, te u budućim korištenjima preuzimamo sve prethodno nepreuzete poruke. 

Pritom koristimo *batch* zahtjeve Googleovog API-a. Oni nam omogućuju simultano primanje većeg broja mailova bez prekidanja veze sa serverom. Iako je dohvaćanje mailova i dalje relativno spor proces, početno punjenje baze je nešto brže.

---

##Frontend 

###Bootstrap

Bootstrap je framework za efikasnu raspodjelu HTML elemenata na web stranici koja izgleda dobro na ekranima različitih veličina. Njegove ekstenzije omogućavaju i izradu naprednijih komponenti, kao
što su lista propadajućih (*collapsable*) elemenata u kojima se prikazuju poruke, te modalni prozori u kojima se nalaze forme za pisanje poruka.  

---

###Blade

Laravelovi *blade* dokumenti su zapravo HTML *templatei* s nekoliko značajki koje olakšavaju generiranje HTML koda. Primjerice, umjesto

> &lt; ? php echo $varijabla; ? >;

Pišemo

> {{ $varijabla }}

Mi smo koristili *bladeove*:
 - za osnovni dizajn stranice kroz koji uključujemo JavaScript i slično,
 - za specifične dizajnove stranica za prijavu i pregled poruka i
 - za dizajn pregleda poruka.

###Forme i javascript

Forme za slanje e-pošte stvaraju se dinamički u Bootstrapovom modalnom prozoru, ovisno o kontekstu poruke (odgovor na poruku, proslijeđivanje, ili nova poruka). Ovo se odvija kroz Javascript skriptu,
koja također vodi brigu o lokalizaciji formi.

Označavanje formi također je izvedeno kroz klijentski javascript kod, koristeći jQuery-jev ajax poziv za POST metodu. Ovakav način rada omogućava ugodno korisničko iskustvo označavanja
poruka bez da se stranica mora osvježavati nakon svakog označavanja.

Validacija formi izvedena je u potpunosti na klijentskoj strani koristeći funkciju koja elemente forme podvrgava usporedbi s regularnim izrazima za ispitivanje valjanosti email adrese, te popunjenosti polja forme.
Kriterij za validaciju zadaje se unutar forme, a preko jQuerya se detektira koje validatore aplicirati na određeno polje.

---

###Lokalizacija

Koristili smo dodatnu biblioteku Carbon za prevođenje sadržaja koji se *renderira* na serveru, te vlastiti lokalizacijski mehanizam za klijentski kod. 
U osnovi GolubMail nudi dva jezika (Hrvatski i Engleski). 

Poruke specifične jezicima pohranjene su u */app/lang/{hr,en}*.
Svakoj jedinici aplikacije - kod nas su to dio aplikacije koji prijavljuje korisnika kroz OAuth te dio aplikacije za prikaz poruka - pridružuje se rječnik. 

U pogledu (*view*) u kojem se prikazuje dani string, naprosto se poziva ključ iz rječnika kojeg Laravel odabire ovisno o jedinici aplikacije. 

---

## Instalacija i pokretanje

### Artisan

Najlakše je instalirati i pokrenuti aplikaciju koristeću Laravelov Artisan server. 

Jedino što je potrebno instalirati ručno je **composer**. To je aplikacija koja se brine o zahtjevima za bibliotekama i njihovim međuovisnostima. Ona će instalirati sve što je potrebno, uključno sa samim Laravelom. Navodimo potrebne daljnje korake za instalaciju i pokretanje aplikacije:

    git clone https://github.com/iliavolyova/golub
    cd golub
    composer update
    php artisan migrate
    php artisan serve

Aplikaciji se sad može pristupiti preko *localhost:8000*.
Također, potrebno je namjestiti ispravne postavke za bazu podataka. Trenutne postavke očekuju korisnika *lmikec* sa svim ovlastima u bazi *golub*. Navedene postavke je moguće promijeniti u **/app/config/database.php**

---

##Apache

Uz određene komplikacije za koje zapravo nema univerzalnog lijeka, aplikaciju je moguće pokrenuti na Apache serveru. To je i učinjeno za potrebe kolegija za koji je ovaj projekt izrađen. Laravel ima vrlo slabu podršku za Apache, te očekuje da će se nalaziti u korijenskom direktoriju servera. To, naravno, općenito nije slučaj. Uz to, teško je postići tzv. semantičke URL-ove poput "/poruke/prikazi/1" umjesto "poruke.php?action=view&id=1". U našem slučaju, postigli smo kompromis u obliku "index.php/semanticki_dio", a argumenti se prenose POST zahtjevom.
