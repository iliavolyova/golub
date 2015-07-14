This is a simple Gmail web-facade project built as part of a college course. 
The rest of the provided information is intended to give our instructors a general idea of how it works.

#Golub Mail

GolubMail je jednostavno web sučelje za upravljanje Gmail računima i e-poštom. Korisnici se spajaju direktno preko svojih Google računa,
te sve akcije obavljaju direktno preko Googleovog API-a. 
Uz slanje i primanje elektroničke pošte, moguće je označavati važnije poruke radi lakšeg pristupa.
Aplikacija podržava sučelje lokalizirano na engleski i hrvatski jezik.

S tehničke strane, GolubMail radi na Laravel 4 frameworku uz nekoliko dodatnih biblioteka, te koristi MySQL bazu.

##Laravel 4

Laravel je framework koji zajedno sa svojim skupom popratnih biblioteka čini zaokruženu cjelinu za izradu web aplikacija po MVC (Model View Controller) paradigmi.
Dodatne biblioteke se dodaju popratnim alatom Composer, koji ujedno vodi brigu o kompatibilnosti među bibliotekama koje ovise jedne o drugima.
Biblioteke se navedu u datateci composer.json te lako instaliraju naredbom 

'''
composer update
'''

##Arhitektura projekta

Svi elementi koji se tiču *izrade* projekta nalaze su direktoriju app. Neki od važnijih poddirektorija za ovaj projekt su: 
- config direktorij sadrži sve konfiguracijske datateke
- controllers - sadrži naše kontrolere
- database - sadrži migracije i inicijalne podatke za bazu podataka
- views - sadrži pregledne datoteke (viewove) koje se pune sadržajem i pretvaraju u HTML koji ide klijentu
- osim ovih direktorija imamo 2 važne datoteke u samom app direktoriju:
    - routes.php: ovdje se čuvaju pozivi funkcija koji pretvaraju zahtjev za određenom rutom proslijeđuju odgovarajućim kontrolerima na obradu
    - filters.php: ovdje je moguće definirati *hook*-ove prije ključnih momenata u radu aplikacije (prije nego je započeta obrada zahtjeva, ili nakon)
    
Navedeni direktoriji su ujedno i ključne datoteke gdje smo pisali kod za ovaj projekt.

##Google OAuth i Gmail API

###Postavke za projekt koji koristi Google API

###Google PHP client biblioteka

##Frontend 

###Bootstrap

###Blade

###Lokalizacija

###Backend i baza