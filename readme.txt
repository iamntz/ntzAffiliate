=== Emag Profitshare ===
Tags: emag, affiliates
Requires at least: 3.0
Tested up to: 3.5
Stable tag: trunk
Contributors: Ionut Staicu

== Description ==
Un plugin simplu și eficient:trece toate link-urile spre emag prin profitshare. Poți urmări noutăți pe blogul meu http://www.iamntz.com/category/goodies/wordpress-profitshare/

Update 16 Iulie 2013:
Plugin-ul este tras pe linie moartă: http://www.iamntz.com/3851/frontend-developer/wordpress-profitshare-update/
Accept pull requests: https://github.com/iamntz/wp-profitshare


== Installation ==
1. Dezarhivează și urcă `profitshare` în folderul `/wp-content/plugins/`
1. Activează plugin-ul din secțiunea `Plugins` din admin-ul WordPress
1. Verifică setările din meniul `Setări Profitshare`
1. Enjoy

== Changelog ==
= 2.0.9 =
Am rezolvat un bug ce făcea link-urile cu slash-uri să nu fie parsate corespunzător

= 2.0.8 =
Am rezolvat un bug ce afișa codul ca fiind invalid în cazul unui upgrade de la versiunea 1.
La dezactivarea opțiunii de scurtare a link-urilor se șterg regulile din `.htaccess`.

= 2.0.7 =
Am rezolvat un bug mic ce făcea quick profitshare să fie afișat tot timpul

= 2.0.6 =
Am adăugat și structurat descrierea plugin-ului plus modul de instalare.

= 2.0.5 =
* am rezolvat un bug ce nu permitea scurtarea URL-urilor în anumite situații
* am adăugat o eroare în cazul în care codul profitshare nu este schimbat
* schimat modul în care functionează quick profitshare; 
* small typos

= 2.0.2 =
* am adăugat un field pentru quick link. Este un feature *foarte* experimental și funcționează doar dacă admin bar este activat și vizibil în tot blogul


= 2.0.1 =
* am actualizat regex pentru a „prinde” și link-urile spre căutări
* se refolosește hash-ul din v.1 (dacă ai făcut upgrade)
* sunt afișate regulile ce trebuiesc adăugate în `.htaccess` pentru a funcționa funcția de scurtare a link-urilor

= 2.0 =
* tot codul a fost rescris de la zero, aducându-se îmbunătățiri majore asupra performanței
* linkurile sunt scurtate folosind api wordpress pentru rescrierea url-urilor, având forma `http://urlblog/go/link_emag_scurtat`
* am adaugat o opțiune de share a profitului. Activând această opțiune, o parte foarte mica a profitului tau este împarțita cu mine. Nu este obligatoriu să activezi această optiune, dar cu siguranță nu mă va deranja dacă o faci!


= 1.1 =
* am eliminat `error_reporting(E_ALL)` (uitat în cod în versiunea anterioară)
* am adăugat opțiunea de a (dez)activa scurtarea URL-urilor

= 1.0 =
First release

== Share your profit! ==
Singurul mod în care îți poți arăta susținerea față de autorul plugin-ului este activarea opțiunii *share your profit!*.