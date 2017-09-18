#BankWS-pankkirajapintakirjasto

BankWS-kirjaston avulla voidaan tehdä pyyntöjä pankkien järjestelmiin Web Service -rajapinnan kautta. Pankkien järjestelmiin tehtävät pyynnöt ovat käytännössä tiedostojen noutoa ja tallennusta joiden avulla rajapinnan yli voi esimerkiksi noutaa viitemaksuaineistot tai tiliotteen. Rajapinta perustuu SOAP-standardiin, jonka lisäksi pankit ovat yhdessä Finanssialan Keskusliiton kanssa määritelleet viestin sisällön muodon. SOAP-viesti on XML-muotoinen, sen lisäksi kaikki SOAP-viestin sisällä kulkeva aineisto on XML-muotoista.

Kirjaston käyttö on pyritty tekemään mahdollisimman yksinkertaiseksi. Pankkien välillä on suuriakin eroja siinä millä tavalla rajapinta toimii, mutta kirjasto pyrkii abstraktoimaan nämä erot mahdollisimman pitkälle. Kirjaston päämääränä on, että käytettävän pankin pystyy vaihtamaan ilman että funktiokutsuja tarvitsee muuttaa millään tavalla.

Kullekin pankille on olemassa oma käsittelijänsä joka hoitaa ko. pankkiin tehtävät kutsut. Ulospäin nämä käsittelijät näyttävät samanlaisilta (metodit ja palautettavat arvot ovat samat), mutta sisäisiltä implementoinneiltaan ne voivat erota rajustikin. Tämän dokumentaation tarkoituksena on avata käsittelijöiden eroja ja muiden luokkien toimintaa.

#Termit

- SOAP

  XML-muotoinen viestirakenne, joka koostuu kolmesta pääelementistä:
  
  - Envelope   
    Koko XML-viestiä ympäröivä rakenne 
  
  - Header  
    Viestin otsikkotiedot, kuten timestampit, digitaaliset allekirjoitukset ym. tiedot, joiden avulla vastaanottaja saa tietoa viestin lähettäjästä.
    
  - Body  
    Varsinainen viestin sisältö.

- ApplicationRequest

  SOAP-viestin `Body`-osassa kulkeva XML-muotoinen sanoma, joka käsitellään pankin päässä. `ApplicationRequest` sisältää kaiken pankin järjestelmiin menevän data, itse SOAP-viestin rakenne on vain tiedon siirtoa varten. Esimerkiksi pankkiin lähetettävän tiedoston sisältö asetetaan `Content`-elementtiin, joka asetetaan `ApplicationRequest`iin muiden tarvittavien kenttien kanssa. `ApplicationRequest` sijoitetaan sen jälkeen base64-enkoodattuna komentoelementtiin yhdessä `RequestHeader`-elementin kanssa ja komentoelementti sijoitetaan SOAP-viestin `Body`-osioon.
  
  Yksinkertainen SOAP-viesti voisi näyttää tältä:

		<soapenv:Envelope xmlns:soapenv="...">
			<soapenv:Header>
			</soapenv:Header>
			<soapenv:Body>
				<cor:downloadFileListin xmlns:cor="..." xmlns:mod="...">
					<mod:RequestHeader>
						<mod:SenderId>11111111</mod:SenderId>
						<mod:RequestId>12345678</mod:RequestId>
						<mod:Timestamp>2012-01-01T12:00:00Z</mod:RequestId>
					</mod:RequestHeader>
					<mod:ApplicationRequest>
						PD94bWwgdmVyc2lvbj0iMS4wIj...GxpY2F0aW9uUmVxdWVzdD4K==
					</mod>
				</cor:downloadFileListin>
			</soapenv:Body>
		</soapenv:Envelope>
  
  `ApplicationRequest` on siis varsinainen sanoma joka halutaan toimittaa pankille, kaikki muu on vain tukemassa viestin lähetystä ja antamassa lisäinfoa lähettäjälle. `soapenv`-elementit ovat SOAP-standardin määrityksen mukaisia ja kaikki mikä tulee `Body`-elementin sisään, on FKL:n tai pankkien omien määritysten mukaista.
  
  `Body`-viestiä seuraava elementti kertoo nimellään mitä komentoa halutaan kutsua. Esimerkissä komento on `downloadFileList`, ja tiedon kulkusuunta on `in`. Pankilta tulevassa vastaussanomassa tämän elementin nimi olisi `downloadFileListOut` ja `ApplicationRequest` olisi vastaavasti `ApplicationResponse`.
  
  `ApplicationRequest` voi olla myös mikä tahansa muu sopivista `ApplicationRequest`-tyyppisistä kyselyistä (`ApplicationRequest`, `CertApplicationRequest` tai `GetBankCertificateRequest`).
  
  Huomaa että esimerkistä puuttuu allekirjoitus- ym. elementit joita varsinaisissa viesteissä tarvitaan. Pankkien omissa dokumentaatioissa on hyviä esimerkkejä kokonaisista XML-sanomista.
  
- CSR

  Certificate Signing Request. Kts. <a href="#createCSR">`createCSR`</a>.

#Kaaviot

<div class="flowchart">
	<div class="group clear left">
		User instantiable
		<div>
			<div class="class w-200 clear left">BankWS</div>
			<div class="class w-200 left">BankHandlerConfig</div>
		</div>
		<p style="clear: both;"></p>
	</div>
	
	<div class="method w-150 clear left">BankWS::getBankHandler</div>
	<div class="class w-150 left">Keychain</div>
	<div class="exception w-150 left">KeychainException</div>
	
	<div class="class w-250 clear left" id="BankHandler">
		BankHandler
		<div class="class">NordeaBankHandler</div>
		<div class="class">SampoBankHandler</div>
		<div class="class">OPBankHandler</div>
		<div class="class">
			SamlinkBankHandler
			<div class="class">AktiaBankHandler</div>
			<div class="class">HandelsbankenBankHandler</div>
			<div class="class">SPBankHandler</div>
			<div class="class">POPBankHandler</div>
		</div>
	</div>
	
	<div class="left w-300">		
		<div class="group">
			Public methods
			<div class="method">BankHandler::getUserInfo</div>
			<div class="method">BankHandler::getCertificate</div>
			<div class="method">BankHandler::downloadFileList</div>
			<div class="method">BankHandler::downloadFile</div>
			<div class="method">BankHandler::uploadFile</div>
			<div class="method optional">BankHandler::renewCertificate</div>
		</div>
		<div class="group">
			SampoBankHandler
			<div class="method" id="BankHandler_getBankCertificate">BankHandler::getBankCertificate</div>
		</div>
	</div>
	
	<div class="clear" style="padding-top: 20px;">
		<div class="method clear left w-200" id="BankHandler_request">BankHandler::request</div>
		<div class="exception left w-200" style="margin-left: 80px;">Exception</div>
	</div>
	
	<div class="clear" style="padding-top: 10px; margin-left: 40px;">
		<div class="method clear left w-200">BankHandler::validateSchema</div>
		<div class="exception left w-200" style="margin-left: 40px;">SchemaValidationException</div>
	</div>
	
	<div style="margin-left: 40px;">
		<div class="clear" style="padding-top: 10px;">
			<div class="class clear left w-250">
				ApplicationRequest
				<div class="class">CertApplicationRequest</div>
				<div class="class">CreateCertificateRequest</div>
				<div class="class">GetBankCertificateRequest</div>
			</div>
		</div>
		
		<div class="left">
			<div class="method w-200" style="margin-left: 30px;">ApplicationRequest::encrypt</div>
			<div class="method w-200" style="margin-left: 30px;">ApplicationRequest::sign</div>
		</div>
	</div>
	
	<div class="clear" style="padding-top: 20px">
		<div class="class clear left w-200">SoapRequest</div>
		<div class="class left w-200" style="margin-left: 30px;">SoapMessage</div>
	</div>
	
	<div class="method clear w-200" style="margin-left: 256px;">SoapRequest::sign</div>
	<div class="method clear left w-200">SoapRequest::request</div>
	<div class="exception left w-200">Exception</div>
	
	<div class="class clear left w-200">SoapResponse</div>
	<div class="class left w-200">SoapResponseHeader</div>
	
	<div class="class clear left w-250">
		ApplicationResponse
		<div class="class">CertApplicationResponse</div>
		<div class="class">CreateCertificateResponse</div>
		<div class="class">GetBankCertificateResponse</div>
	</div>
	
	<p style="clear: both;"></p>
</div>

#Luokat

BankWS-kirjasto on jaettu selkeästi luokkiin tehtävän mukaan. Kirjastoon liittyy neljänlaisia luokkia:

- Pankkikohtaiset käsittelijät
- Tiedonsiirtoluokat
- Poikkeukset
- Tietoluokat

##BankWS

####Kuvaus

Rajapinnan pääluokka, käytettäessä rajapintaa tarvitaan sisällyttää vain tämä luokka ja kaikki komennot tapahtuvat aluksi tämän luokan kautta. BankWS-luokan kautta voidaan luoda pankkikohtainen käsittelijä, jonka avulla kutsuja pankkiin päin voidaan lähettää.

Kaikki BankWS-luokan sisältämät metodit ovat staattisia, joten BankWS-luokkaa ei koskaan tarvitse alustaa `new`-operaattorilla.

####Metodit

#####public static function getBankHandler($bankHandler, BankHandlerConfig $config)

Palauttaa annetun pankin käsittelijän alustettuna annetulla pankkikohtaisella asetusluokalla. `$bankHandler`-muuttujassa voidaan käyttää BankWS-luokkaan ennalta määrättyjä vakioita, jotka ovat:

    BankWS::Sampo
    BankWS::Nordea
    BankWS::OP
    BankWS::Handelsbanken
    BankWS::POP
    BankWS::SP
    BankWS::Aktia
        
Esimerkiksi Nordean pankkikohtainen käsittelijä saadaan luotua seuraavanlaisella koodilla:

	use BankWS;
	$config = new BankHandlerConfig(...);
	$bankHandler = BankWS::getBankHandler(BankWS::Nordea, $config);

<h5 id="createCSR">public static function createCSR($options = array())</h5>

Luo varmennepyynnön (<a href="http://en.wikipedia.org/wiki/Certificate_signing_request">Certificate signing request</a>) joka lähetetään pankille varmenteiden vahvistamista varten.

Kirjaston päässä luodaan yksityinen avain ja julkista avainta vastaava varmennepyyntö, joka liitetään mukaan CSR-pyyntöön. Varmennepyyntöä ei voi käyttää tietoliikenteessä ennen kuin se on varmistettu ja allekirjoitettu pankin toimesta. Pankki tarkastaa että kysely tulee keneltä pitääkin (PIN-koodin tms. avulla) ja allekirjoittaa varmennepyynnön pankin juurivarmenteella. Tuloksena on käyttökelpoinen julkinen avain jonka pankki lähettää takaisin, jolloin käytössä on toimiva avainpari jota voidaan käyttää pankin ja kirjaston väliseen tietoliikenteeseen.

Varmennepyynnöt luodaan pankkikohtaisissa käsittelijöissä, kukin omalla tavallaan. Pankeilla voi olla erilaisia vaatimuksia varmennepyyntöjen suhteen, esimerkiksi Sampo vaatii varmennepyynnön olevan 2048-bittinen, kun muille pankeille riittää 1024 bittiä. Tutustu pankkikäsittelijöiden `getCertificate`-metodeihin.

`createCSR`-metodi ei tallenna luotuja avaimia `Keychain`iin (koska kyseessä on staattinen metodi eikä sillä ole pääsyä pankkikäsittelijän avainnippuun), vaan se palauttaa arrayn josta löytyy yksityinen avain ('private'), varmennepyyntö ('csr') sekä HMAC-tiiviste, jonka jotkut pankeista vaativat.

#####public static function outputXML($xml, $return = false)

Tulostaa XML-muotoisen stringin, tai vaihtoehtoisesti palauttaa jos `$return` on `true`.

#####public static function generateRequestId()

Luo satunnaisen tunnuksen jota käytetään SOAP-viestien yksilöivänä tunnisteena. Käytetään BankHandler.php:ssä.
	
##Pankkikohtaiset käsittelijät

Pankkikohtaiset käsittelijät hoitavat pyyntöjen luonnin sekä vastausten käsittelyn. Kaikki käsittelijät implementoivat samat funktiot mutta sisäinen toteutus saattaa erota huomattavasti. Jokainen käsittelijä perii `BankHandler`-luokasta, joka on rakennettu Finanssialan Keskusliiton tekemien määritysten mukaiseksi. Niiltä osin miten kunkin pankin oma toteutus eroaa FKL:n määrityksestä, on luokkia täydennetty pankkikohtaisilla toiminnoilla.

Pankkikohtaisia käsittelijöitä ei koskaan alusteta suoraan `new`-operaattorilla, vaan ne tulee aina luoda `BankWS`-luokan `getBankHandler`-metodin kautta.

###BankHandler

BankHandler on abstrakti luokka, joka siis määrittelee pohjan kaikille pankkikäsittelijöille, mutta sitä ei voi suoraan alustaa.

####Vakiot

`FILETYPE_REFERENCE_PAYMENTS`

Pankkikohtainen viitemaksuaineistojen tunnus

`FILETYPE_FINVOICE_SEND`

Pankkikohtainen lähtevien Finvoice-aineistojen tunnus

`FILETYPE_FINVOICE_RECEIVE`

Pankkikohtainen saapuvien Finvoice-aineistojen tunnus

`FILETYPE_FINVOICE_FEEDBACK`

Pankkikohtainen Finvoice-aineistojen palautteen tunnus

####Metodit

#####public function __construct(BankHandlerConfig $config, $keychain)

Alustaa luokan. Tallentaa luokan tietoihin `keychain`:n ja pankkikohtaisen asetusluokan sekä tallettaa pankkikäsittelijän nimen. Tätä metodia kutsutaan automaattisesti aina kun jokin pankkikohtainen käsittelijä luodaan.

#####abstract public function getUserInfo($config = null)`

Pankkikohtaisen käsittelijän implementoitava funktio, joka noutaa pankista kyseisen käyttäjän tiedot ja palauttaa `UserInfo`-tietueen. `$config`-muuttujassa kutsuva funktio voi syöttää funktiolle valinnaisia lisätietoja.

#####abstract public function getCertificate($config = null)`

Pankkikohtaisen käsittelijän implementoitava funktio, joka noutaa yhteyden muodostamiseen vaadittavan sertifikaatin pankista. Sertifikaattien nouto eroaa pankkien välillä suuresti, joten jokaisen pankkikäsittelijän on määritettävä oma hakufunktionsa.

Palauttaa arrayn jossa on seuraavat kentät:

- `CustomerSigningPrivate`
  Yksityinen avain, raakamuodossa (string).
- `CustomerSigningPublic`
  Julkinen avain, raakamuodossa (string).
- `SoapRequest`
  Kyselyyn käytetty SoapRequest-XML.
- `ApplicationRequest`
  Kyselyyn käytetty ApplicationRequest-XML.
- `SoapResponse`
  Kyselyyn vastauksena saatu XML.
  
Palautettua arrayta ei itseasiassa tarvitse käyttää mihinkään, sillä `getCertificate`-metodit tallentavat noudetut avaimet suoraan `Keychain`iin.

#####public function downloadFileList($config = null)

Tiedostolistauksen lataaminen pankista. Oletusimplementointi, joidenkin pankkien kohdalla toteutus saattaa poiketa tästä metodista, mutta FKL:n määritysten mukaan tiedostojen lataaminen kuuluisi mennä näin.

`$config`-array voi sisältää seuraavat kentät:

- `Command`
  Komento joka pankille lähetetään, oletuksena DownloadFileList.
- `StartDate`
  Alkupäivämäärä josta lähtien tiedostot noudetaan. Muodossa Y-m-d. Oletuksena `null`.
- `EndDate`
  Loppupäivämäärä johon asti tiedostot noudetaan. Muodossa Y-m-d. Oletuksena `null`.
- `Status`
  Noudettavien tiedostojen status.
  - ALL: Noudetaan kaikki mahdolliset tiedostot
  - NEW: Noudetaan noutamattomat
  - DLD: Noudetaan jo aiemmin ladatut
- `Filetype`
  Ladattavan tiedostolistauksen tiedostotyyppi. Oletuksena `FILETYPE_REFERENCE_PAYMENTS`.
  
Palauttaa `FileList`-tyyppisen tietueen, joka sisältää jokaisen löytyneen tiedoston tiedot, mukaanlukien `FileReference`-kentän, jota tarvitaan tiedoston lataamiseen.
  
#####public function downloadFile($config = null)

Yhden tai useamman tiedoston lataaminen pankista.

`$config`-array voi sisältää seuraavat kentät:

- `Command`
  Komento joka pankille lähetetään, oletuksena DownloadFile.
- `FileReferences`
  Noudettavien tiedostojen tiedot. Array, jonka pituus oltava vähintään yksi.

Palauttaa tiedoston raakadatana (string).

#####public function uploadFile($config = null)

Tiedoston lähetys pankkiin

`$config`-array voi sisältää seuraavat kentät:

- `Command`
  Komento joka pankille lähetetään, oletuksena UploadFile.
- `FileType`
  Lähetettävän tiedoston tiedostotyyppi. Oletuksena `FILETYPE_REFERENCE_PAYMENTS`.
- `Content`
  Tiedoston sisältö sellaisenaan (ei base64-enkoodattuna).

Palauttaa SoapResponsen.

`protected function function validateSchema($schema, $values)`

Jokainen pankkikohtainen käsittelijä määrittelee oman skeemansa, jota jokaisen lähtevän kyselyn tulee noudattaa. Tämä funktio toimii skeeman tarkastajana. Parametreina funktio ottaa tarkastettavan skeeman sekä arvot joita kyselyssä lähetetään. Tätä funktiota kutsutaan `BankHandler::request` -funktiossa ennen kyselyn lähettämistä, eikä tätä funktiota ole tarvetta (tai mahdollistakaan) kutsua itse.

Funktio käy jokaisen arvon läpi ja vertaa sitä skeemassa määriteltyihin sääntöihin. Jos arvo ei läpäise sääntöjä, heittää `SchemaValidationException`.

`protected function function request($applicationRequestValues, $config = array())`

Tämä funktio hoitaa itse tiedonsiirron, eli kyselyjen lähettämisen. Jokainen pankkikäsittelijän toteuttama tiedonsiirtometodi kutsuu tätä funktiota, parametrina kyselyä varten luomansa arvot.

Funktiossa tarkastetaan kyselyn oikeellisuus `validateSchema`-metodin avulla ja alustetaan oikeantyyppinen [`ApplicationRequest`] sekä viestin lopulta lähettävä SoapRequest.

Tässä funktiossa myös tarvittaessa allekirjoitetaan ja salataan viesti, jos pankkikäsittelijä niin on kyselyä luodessaan vaatinut.

Palauttaa SoapResponse-olion tai heittää poikkeuksen.

###SamlinkBankHandler

Samlink hoitaa pankkiliikenteen Aktialle, Säästöpankille, Paikallisosuuspankeille sekä Handelsbankenille.

Säästöpankin ja Paikallisosuuspankin kohdalla ei ole pankkikohtaisia eroja vaan kaikki noudattavat Samlinkin määrityksiä suoraan. `SPBankHandler` ja `POPBankHandler` ovat siis tyhjiä luokkia jotka vain perivät `SamlinkBankHandler`:n.

`AktiaBankHandler`:n kohdalla tilanne on melkein sama, poikkeuksena se että Aktia määrittää omassa luokassaan omat yhteysosoitteensa. Muilta osin luokka on tyhjä.

`HandelsbankenBankHandler` noudattaa Samlinkin määritystä muilta osin paitsi tiedostotyyppien suhteen. Handelsbanken käyttää omia tiedostotyyppien tunnisteita.

####Vakiot

`WEB_SERVICE_ADDRESS`

Samlinkin Web Service -rajapinnan yhteysosoite. https://ws.samlink.fi/services/CorporateFileService

`CERT_SERVICE_ADDRESS`

Samlinkin Web Service -rajapinnan varmennepalvelun yhteysosoite. https://ws.samlink.fi/wsdl/CertificateService.xml

`WEB_SERVICE_TEST_ADDRESS`

Samlinkin Web Service -rajapinnan testausyhteysosoite. Sama kuin normaali yhteysosoite.

`CERT_SERVICE_TEST_ADDRESS`

Samlinkin Web Service -rajapinnan varmennepalvelun testausyhteysosoite. Sama kuin normaali varmennepalvelun yhteysosoite.

`FILETYPE_REFERENCE_PAYMENTS`

OP

`FILETYPE_FINVOICE_SEND`

VL

`FILETYPE_FINVOICE_RECEIVE`

VN

`FILETYPE_FINVOICE_FEEDBACK`

VP

####Metodit

#####public function getUserInfo($config = null)

Heittää poikkeuksen, `getUserInfo`-toimintoa ei ole implementoitu Samlinkin rajapinnassa.

#####public function getCertificate($config = null)

Noutaa sertifikaatin Samlinkin varmennepalvelusta. Samlinkin varmennepalvelu käyttää hyvin lähelle samaa toteutusta varmenteiden noudossa kuin Osuuspankki. Samlinkin toteutuksessa on kuitenkin muutama pieni ero, esimerkiksi Samlink suorittaa base64-enkoodauksen kahdesti sisällölle, kun Osuuspankki tekee sen vain kerran.

###NordeaBankHandler

####Vakiot

`WEB_SERVICE_ADDRESS`

Nordean Web Service -rajapinnan yhteysosoite. https://filetransfer.nordea.com/services/CorporateFileService

`CERT_SERVICE_ADDRESS`

Nordean Web Service -rajapinnan varmennepalvelun yhteysosoite. https://filetransfer.nordea.com/services/CertificateService

`WEB_SERVICE_TEST_ADDRESS`

Nordean Web Service -rajapinnan testausyhteysosoite. Sama kuin normaali yhteysosoite.

`CERT_SERVICE_TEST_ADDRESS`

Nordean Web Service -rajapinnan varmennepalvelun testausyhteysosoite. Sama kuin normaali varmennepalvelun yhteysosoite.

`FILETYPE_REFERENCE_PAYMENTS`

KTL

`FILETYPE_FINVOICE_SEND`

LAHLASKUT

`FILETYPE_FINVOICE_RECEIVE`

HAELASKUT

`FILETYPE_FINVOICE_FEEDBACK`

HYLLASKUT

####Metodit

Nordean rajapinta noudattaa uskollisesti FKL:n määrityksiä. Tiedostojen lataus- ja lähetysmetodit peritään suoraan BankHandler-luokasta ilman lisäyksiä tai muutoksia.

#####public function getUserInfo($config = null)

Noutaa tiedon käyttäjän ladattavissa olevista tiedostotyypeistä. 

Palauttaa UserInfo-olion.

#####public function getCertificate($config = null)

Noutaa sertifikaatin Nordean varmennepalvelusta ja tallentaa sen `keychain`:iin.

###OPBankHandler
###SampoBankHandler

##Poikkeukset

###Exception

####Kuvaus

Yleinen poikkeus.

###KeychainException

Avainnippuun liittyvä poikkeus.

Heitetään jos avainnippuun tallennettavan avaimen nimi on vääränmuotoinen (sisältää '-' -merkin).

###SchemaValidationException

Pyyntöjen oikeamuotoisuuden varmistamiseen liittyvä poikkeus. Heitetään jos lähetettävä pyyntö ei ole oikean muotoinen. Kukin pankkikohtainen käsittelijä määrittelee itse oman skeemansa jota vasten sanomat validoidaan.

##Tiedonsiirtoluokat

###ApplicationRequest

####Kuvaus

`ApplicationRequest` on `SoapRequest`in sisällä kulkeva varsinainen viesti pankille päin. Vastauksena `ApplicationRequest`iin tulee `ApplicationResponse`. `ApplicationRequest` sisältää kaikki FKL:n ja pankkien omissa määrityksissä kuvatut kentät tiettyä kyselytyyppiä varten.

`ApplicationRequest`-luokasta on myös erikoistuneimpia versioita tietynlaisiin toimenpiteisiin, kuten varmenteiden noutoihin. Yksinkertaisimmillaan nämä erikoistuneet luokat muuttavat vain XML-tagin nimeä jolla `ApplicationRequest` kulkee. Esimerkiksi oletuksena XML-sanomaan `ApplicationRequest` upotetaan `<ApplicationRequest>` tagin sisään, mutta sertifikaatin noudossa tagina voi olla `<CertApplicationRequest>`.

`ApplicationRequest`-luokka vastaa `ApplicationRequest`-elementin täyttämisestä annettujen parametrien mukaan. Valmis luokka annetaan lopulta parametrina `SoapRequest`-olioon, joka upottaa `ApplicationRequest`in `SoapMessage`en.

####Metodit

#####public function __construct($config = array(), $keychain)

`ApplicationRequest` luodaan `BankHandler`in `request`-metodissa, jossa luokalle annetaan parametriksi array. Array sisältää yhden kentän, `namespace`-määrityksen joka kertoo mitä namespacea lopullisen XML-sanoman muodostamiseen tulee käyttää (namespace upotetaan osaksi XML-sanomaa ja se vaaditaan joidenkin pankkien osalta jotta viesti menee läpi).

Konstruktorissa luodaan runko XML-viestille `DOMWrapper`-luokan avulla.

#####public function setValue($key, $value = null, $parent = null)

Sallii yksittäisen arvon lisäämisen `ApplicationRequest`iin. Jos `$parent` on `null`, uusi elementti luodaan `ApplicationRequest`in juurielementin alle. Jos `$parent` on annettu ja se on tyyppiä `DOMNode`, uusi arvo luodaan annetun elementin alle.

Esimerkiksi `...->setValue('Foo', 'Bar')` johtaisi seuraavanlaiseen rakenteeseen:

    <ApplicationRequest>
        <Foo>Bar</Foo>
    </ApplicationRequest>

`$key` voi olla myös array, jolloin `$key` iteroidaan läpi ja avain/arvo-parit tallennetaan.

#####public function setValues($key, $value = null)

Alias `setValue`-metodille.

#####public function __toString()

Palauttaa `ApplicationRequest`in XML-muotoisena, tarvittaessa base64-enkoodattuna jos pankkikäsittelijä on näin määrännyt.

#####public function getXML()

Palauttaa `ApplicationRequest`in XML-muotoisena.

#####public function setBase64Encode($bool)

Määrittää palautetaanko XML-muoto base64-enkoodattuna.

#####public function getBase64EncodedContent()

Palauttaa 'ApplicationRequest`in XML-muodon base64-enkoodattuna, huolimatta siitä, onko `useBase64Encode` true vai false.

#####public function sign($options)

Allekirjoittaa `ApplicationRequest`-elementin XML-allekirjoitusten standardin mukaisesti (http://www.w3.org/TR/xmldsig-core/) käyttäen apuna xmlseclib-kirjastoa (lib/xmlseclib/).

Parametreina voi syöttää yksityisten ja julkisten avainten osoitteet, sekä allekirjoituksessa mahdollisesti käytettävän passphrasen. Oletuksena avaimet kuitenkin noudetaan `Keychain`ista.

`ApplicationRequest`-elementin allekirjoitus etenee seuraavin askelin:

- Tarkistetaan että yksityinen ja julkinen avain on määritetty, ja että ne ovat luettavissa.
- Luodaan uusi `XMLSecurityDSig`-olio, eli digitaalinen allekirjoitus
- Määritetään kanonikalisointimuoto. Kanonikalisoinnilla määritetään tapa, jolla XML-dokumentti normalisoidaan johonkin tiettyyn muotoon. Kanonikalisoidusta muodosta poistetaan esimerkiksi kaikki turhat välilyönnit. Vaikka rivinvaihdot muuttuisivat tiedonsiirron aikana, pankin päässä tehdään vastaava kanonikalisointi kuin allekirjoitettaessa, jolloin allekirjoitus täsmää vaikka viesti ei olisi 100% yhtenevä alkuperäisen kanssa.
- Luodaan referenssi `ApplicationRequest`-dokumenttiin, ja määritettään että kyseessä on "enveloped signature", eli allekirjoitus ympäröi allekirjoitettavaa sisältöä.
- Luodaan uusi `XMLSecurityKey`-olio, eli yksityinen avain
- Allekirjoitetaan `XMLSecurityDSig`-olio `XMLSecurityKey`-oliolla
- Lisätään julkinen avain viestiin mukaan viestiin. Julkisella avaimella vastaanottaja voi varmistaa viestin alkuperän ja muuttumattomuuden.
- Lopuksi lisätään allekirjoitus varsinaiseen dokumenttiin.

`ApplicationRequest`-elementin allekirjoitus vaaditaan yleensä kaikissa toimenpiteissä, paitsi varmenteiden noudossa (varmenteita noutaessa ei ole vielä olemassa avainta jolla allekirjoittaa). Varmenteiden noudossa viestin alkuperä varmistetaan muilla keinoilla, kuten pankkien toimittamilla PIN-koodeilla/aktivointiavaimilla.

#####public function encrypt($config)

`Encrypt`-metodi suorittaa salauksen `ApplicationRequest`-elementille. Ainoastaan viestin vastaanottaja pystyy purkamaan salauksen, koska viesti salataan vastaanottajan (pankin) julkisella avaimella. Ainoastaan vastaavan yksityisen avaimen haltija voi purkaa salauksen.

Viestin salaus noudattaa vastaavanlaista kaavaa kuin allekirjoitus:

- Varmistetaan että salaukseen käytettävä julkinen avain löytyy
- Luodaan `XMLSecurityKey`t joilla salaus tehdään.
- Määritetään mikä elementti dokumentista salataan (`documentElement`)
- Suoritetaan salaus
    
###CertApplicationRequest

####Kuvaus

`CertApplicationRequest` periytyy `ApplicationRequest`-luokasta, ja ei tee muuta kuin määrittää uudestaan `ApplicationRequest`-juurielementin nimen `CertApplicationRequest`iksi. 

###CreateCertificateRequest

####Kuvaus

`CreateCertificateRequest` on ainoastaan Sampon käyttämä `ApplicationRequest`-tyyppi, joka vastaa muiden pankkien `CertApplicationRequest`-tyyppiä.

`CreateCertificateRequest` periytyy `CertApplicationRequest`-luokasta, ja määrittää uudestaan `CertApplicationRequest`-juurielementin nimen `CreateCertificateRequest`iksi sekä ylikirjoittaa `__toString`-metodin, koska `CreateCertificateRequest` on salattua sisältöä eikä base64-enkoodausta tarvita.

###GetBankCertificateRequest

`GetBankCertificateRequest` on ainoastaan Sampon käyttämä `ApplicationRequest`-tyyppi, jolla noudetaan pankin julkinen varmenne sisällön salaamista varten. Muilla pankeilla ei ole vastaavaa toimintoa, sillä Sampo on ainoa pankki joka käyttää sisällön salausta.

###SoapRequest

####Kuvaus

`SoapRequest` on luokka, joka hoitaa varsinaisen viestien lähetyksen ja vastauksen vastaanottamisen. `SoapRequest` luodaan `BankHandler`issa, josta se saa parametrikseen arrayn, jossa on muiden asetusten lisäksi myös SOAP-viestiin sisällytettävä `ApplicationRequest`.

Varsinainen lähetettävä viesti on `SoapMessage`-olio, joka luodaan konstruktorissa.

####Metodit

#####public function __construct($options = null, $keychain = null)

Alustaa luokan ja parsii annetut asetukset. Luo `SoapMessage`-olion viestin varsinaista sisältöä varten.

#####public function request()

Lähettää `SoapMessage`n ja käsittelee paluuvastauksen.

Jos jossain kohdassa ennen viestin lähetystä on määritelty `BWS_APP_REQ_DEBUG`, `BWS_SOAP_REQ_DEBUG` tai `BWS_RESPONSE_DEBUG` -vakiot millä tahansa arvoilla, suoritus keskeytyy ja `request`-metodi tulostaa vakion määrittämän viestin sisällön ja keskeyttää suorituksen. `BWS_RESPONSE_DEBUG` lähettää viestin ja keskeyttää suorituksen vastauksen saapumisen jälkeen.

Viestin lähetykseen käytetään cURL-kirjastoa. Alunperin SOAP-viestien lähetykseen oli useampikin vaihtoehto. PHP:ssä on itsessään olemassa SOAP-kirjasto joka osaa lähettää yksinkertaisia SOAP-viestejä mutta ei kyennyt käsittelemään monimutkaisempia toimintoja, kuten allekirjoituksia ja salausta. Sen jälkeen käyttöön otettiin WSO2 WSF/PHP -kirjasto, joka on C-laajennus PHP:hen. Laajennus jouduttiin kuitenkin hylkäämään useiden kriittisten bugien vuoksi, ja lopulta tiedonsiirto päädyttiin toteuttamaan perinteisesti cURL:in kautta. cURL:in kautta lähetettäessä menetetään joitakin tietoturvaominaisuuksia, kuten vastausviestin allekirjoitusten automaattinen tarkastaminen (tämäkin toiminto olisi kuitenkin mahdollista toteuttaa käsin). On kuitenkin todettu, että SSL-suojattu yhteys pankkiin on riittävä tietoturva BankWS-kirjastoon päin.

cURL-viestiin lisätään automaattisesti `SOAPAction`-header, jonka arvona on lähetettävän toiminnon nimi (esim. downloadFileIn). Jotkin pankit eivät suostu käsittelemään viestiä ilman tätä headeria.

Jos vastausviestin vastaanottaminen onnistui, palautetaan uusi `SoapResponse`-olio joka on alustettu vastausviestillä.

#####public function getBody()

Luo SOAP-viestin Body-osion käyttäen viestille määriteltyä <a href="#sanomapohjat">Sanomapohjaa</a> ja parametreina annettua `ApplicationRequest`-elementtiä.

#####public function sign()

Allekirjoittaa `SoapMessage`n. Varsinainen allekirjoitusmetodi löytyy `SoapMessage`-luokasta.


###Response

####Kuvaus

Sisältää jaetut metodit vastaussanomille. Kaikki `ApplicationResponse`- ja `SoapResponse`-tyyppiset viestit perivät tästä luokasta.

####Metodit

`protected function validate($dom)`

Tarkistaa vastaussanoman ja yrittää etsiä vastauskoodia. Jos viestistä löytyy mikä tahansa muu vastauskoodi kuin `0`, on se merkki virheestä, jolloin funktio heittää poikkeuksen.

###ApplicationResponse

####Kuvaus

`ApplicationResponse` on juuriluokka kaikille `ApplicationResponse`-tyyppisille vastausviestelle. `ApplicationResponse` on SOAP-vastauksen sisällä kulkeva varsinainen vastaussanoma.

`ApplicationResponse` implementoi `Iterator`-rajapinnan, jonka avulla `ApplicationResponse`-elementtiä voi suoraan iteroida esimerkiksi foreachin avulla.

####Metodit

#####public function __construct($xml, $keychain)

Tallentaa `Keychain`in ja kutsuu `load`-metodia.

#####public function load($xml, $root = true)

Parsii rekursiivisesti string-muotoisen XML-sanoman ja muodostaa siitä `DOMWrapper`-muotoisen olion, jota voidaan käyttää jatkokäsittelyssä.

#####public function getArray($array = null)

Palauttaa array-muotoisen representaation vastaussanomasta. Voi tulla tarpeen niissä tilanteissa joissa luokan suora iterointi Iterator-rajapinnan avulla ei ole tarpeeksi joustavaa.

#####public function __set($key, $value)

Asettaa vastaukseen annetun arvon.

#####public function __get($key)

Palauttaa annetun arvon vastauksesta

#####public function current()
#####public function key()
#####public function rewind()
#####public function next()
#####public function valid()

`Iterator`-rajapinnan toteutus.

###EncryptedData

####Kuvaus

Salatun `ApplicationResponse`-vastaussanoman kuvaus. Sampo palauttaa vastauksensa salatussa muodossa, jolloin se täytyy purkaa ennen käyttöä. Vastaus on `EncryptedData`-elementin sisällä XML-vastaussanomassa.

`EncryptedData` periytyy `ApplicationResponse`-luokasta. Ainoastaan Sampo käyttää tätä vastaustyyppiä.

####Metodit

#####public function __construct($xml, $keychain)

Muuten vastaava kuin perityn luokan konstruktori, mutta suorittaa salauksen purun ennen XML-aineiston parsimista.

#####public function decrypt($xml)

Purkaa salatun XML-muotoisen aineiston yksityisen salausavaimen avulla.

###CertApplicationResponse

####Kuvaus

Vastaus varmennenoutopyyntöön. Vastaa muilta osin `ApplicationResponse`-vastaussanomaa, mutta sisältää apumetodin varmenteiden poimimiseen vastauksesta.

####Metodit

#####public function getCertificates()

Etsii ja palauttaa vastaussanomasta löytyneet varmenteet. Varmenteet löytyvät yleensä vastaussanoman `<Certificates>`-elementistä, mutta Sampo asettaa varmenteet suoraan vastauksen juureen nimettyinä varmenteina.

Palauttaa arrayn löytyneistä varmenteista.

###CreateCertificateResponse

####Kuvaus

Sampon vastaus varmennepyyntöön on nimetty muista pankeista poiketen `CreateCertificateResponse`ksi. Luokka perityy `CertApplicationResponse`sta mutta ei esittele uusia funktioita. Varmenteiden noutoon käytetään `CertApplicationResponse`n `getCertificates`-funktiota.

###GetBankCertificateResponse

####Kuvaus

Sampon vastaus pankin varmenteen noutokyselyyn. Vastaava rakenne kuin `CreateCertificateResponse`lla.

###SoapResponse

####Kuvaus

`SoapResponse` kuvaa pankilta tulevaa vastaussanomaa vastauksena `SoapRequest`-kutsuun.

####Metodit

#####public function __construct($xml = null, $keychain = null);

Alustaa muuttujat sekä kutsuu `load`-metodia jos `$xml` ei ole `null`.

#####public function load($xml)

Parsii string-muotoisen XML-datan, noutaa otsaketiedot ja tallentaa ne `SoapHeader`-olioon sekä rakentaa oikeanmuotoisen `ApplicationResponse`-vastauksen.

`ResponseHeader` sisältää vastaavanlaisia tietoja kuin pyyntöviestissä oleva `RequestHeader`. Nämä tiedot iteroidaan läpi ja tallennetaan `SoapHeader`-olioon.

`ResponseHeader`-elementtiä seuraava elementti on varsinainen `ApplicationResponse`-vastaus. Vastaussanomasta riippuen, elementti voi olla jokin seuraavista:

- `ApplicationResponse`
- `CertApplicationResponse`
- `CreateCertificateResponse`
- `GetBankCertificateResponse`
- `EncryptedData`
    
Metodi tunnistaa tämän elementin ja luo sitä vastaavan olion. Sisältö saattaa olla base64-enkoodattua, metodi ottaa myös sen huomioon ja kutsuu lopulta vastaussanomaa vastaavan olion konstruktoria. `ApplicationResponse` tallennetaan `SoapResponse`-olion `applicationResponse`-muuttujaan.

Heittää poikkeuksen jos viestistä ei löydy `ResponseHeader`-elementtiä tai jotakin `ApplicationResponse`-elementeistä.

###SoapResponseHeader

####Kuvaus

Rakenne `SoapResponse`sta löytyvien `ResponseHeader`-tietojen tallennukseen. Implementoi Iterator-rajapinnan, joten toimii käytännössä kuten array. Ei muuta toiminnallisuutta kuin tietojen tallennus.

##Apuluokat

###DOMWrapper

####Kuvaus

Apuluokka DOMDocument-luokan käsittelyyn. Luokkaa käytetään sisäisesti helpottamaan joitakin toimenpiteitä XML-aineiston muodostuksessa, kuten lapsielementtien lisäämistä ja attribuuttien määrittämistä.

###Keychain

Luokka, jonka tehtävänä on hoitaa kirjastossa käytettävien avainten (sertifikaattien) tallennus ja käyttö. Luotaessa `BankWS`-oliota, on parametrina annettavaan `BankHandlerConfig`-olioon määritettävä `keyfolder`-muuttuja, joka määrittää mistä kansiosta jo tallennetut avaimet löytyvät sekä mihin uudet avaimet tallennetaan.

Keychain toimii välikätenä levylle tallentamisen kanssa, joten yksikään luokka ei tallenna avaimia suoraan levylle tai nouda niitä suoraan levyltä. Tämä mahdollistaa sen, että myöhemmin avaimien tallennus esimerkiksi tietokantaan on helppo toteuttaa vain tätä luokkaa muuttamalla.

####Metodit

#####public function __construct($bankHandler, $keychainFolder)

Tämä luokka alustetaan BankWS::getBankHandler-funktion yhteydessä, joten sitä ei tarvitse koskaan luoda manuaalisesti. Luokka saa parametrina käytettävän pankin käsittelijäluokan sekä tiedon avainten tallennuskansiosta. Avainten tallennuskansio on määritelty käyttäjän toimesta BankHandlerConfig-luokassa.

#####public function addKey($name, $key)

Tallentaa annetun avaimen annetulla nimellä avainkansioon. Heittää `KeychainException`in jos annettu nimi sisältää '-' -merkin. Ei palauta mitään.

#####public function removeKey($name)

Poistaa annetulla nimellä olevan avaimen avainkansiosta. Palauttaa boolean-arvon riippuen toimenpiteen onnistumisesta.

#####public function getKey($name)

Palauttaa annetulla nimellä olevan avaimen avainkansiosta, tai false jos avainta ei löydy.

#####public function function getKeyPath($name, $only_if_exists = true)

Palauttaa avaimen tallennuskansion. Käytetään lähinnä luokan sisäisesti tallennuspolkujen normalisointiin. Jos `$only_if_exists` on `true`, palauttaa falsen jos tiedostoa ei ole jo olemassa.

##Tietoluokat

###FileList
###UserInfo
###BankHandlerConfig

####Kuvaus

`BankHandlerConfig` on pankkikohtaisen käsittelijän asetusluokka. Noudettaessa pankkikäsittelijää `getBankHandler`-funktiolla, on parametrina annettava oikeilla arvoilla alustettu `BankHandlerConfig`-olio.

####Muuttujat

#####$customerId

Asiakastunnus / sopimusnumero. Pankki käyttää tätä tietoa käyttäjän yksilöimiseen.

#####$targetId

Aineistoerän tunnus.

#####$keyFolder

Avainten (varmenteiden) tallennuskansio

#####$testmode

Boolean-arvo. Asettaa pankkiyhteyden testitilaan, jolloin mitään muutoksia ei oikeasti tehdä. Testitilan määritys eroaa pankeittain jonkin verran. Ei pakollinen, oletuksena false.

#####$softwareId

Lähettävän ohjelman tunnus. Oletuksena BankWS, voi olla esim. Laskumaatti.

####Metodit

#####__construct($confit = array())

Luokan muuttujien arvot voidaan syöttää myös suoraan arrayna konstruktorille:

    $bankHandlerConfig = new BankHandlerConfig(array(
    	'customerId' => 12345678,
    	'targetId' => '111111A1',
    	'keyFolder' => 'keys/'
    ));

###SoapMessage

####Kuvaus

Varsinainen SOAP-viesti joka lähetetään `SoapRequest`-luokan `request`-metodissa.

####Metodit

#####public function __construct($contents = null)

#####public function __toString()

#####public function getXML()

#####public function buildHeader()

#####public function buildBody($contents)

#####public function sign($options)

#Sanomapohjat

Sanomapohjia (template) käytetään viestien muodostuksessa. Pohjat löytyvät template/ -kansiosta, ja niiden tarkoituksena on helpottaa ApplicationRequest-sanomien rakentamista. Kullakin pankilla on hieman erilaiset vaatimukset XML-viestin muodosta, jonka takia jokainen pankki tarvitsee oman pohjansa. Sampolla, OP:lla ja Nordealla on normaalin ApplicationRequest-sanomapohjan lisäksi omat pohjat varmennepalvelun käyttöä varten.

Kirjasto sisältää seuraavat sanomapohjat:

- `templates/NordeaApplicationRequest.xml`
- `templates/NordeaCertApplicationRequest.xml`
- `templates/OpApplicationRequest.xml`
- `templates/OpCertApplicationRequset.xml`
- `templates/SamlinkApplicationRequest.xml`
- `templates/SampoApplicationRequest.xml`
- `templates/SampoCreateCertificateRequest.xml`
- `templates/SampoGetBankCertificateRequest.xml`

`templates/NordeaApplicationRequest.xml` näyttää seuraavanlaiselta:

	<cor:{Command} xmlns:cor="http://bxd.fi/CorporateFileService"
				   xmlns:mod="http://model.bxd.fi">
		<mod:RequestHeader>
			<mod:SenderId>{SenderId}</mod:SenderId>
			<mod:RequestId>{RequestId}</mod:RequestId>
			<mod:Timestamp>{Timestamp}</mod:Timestamp>
			<mod:Language>FI</mod:Language>
			<mod:UserAgent>{SoftwareId}</mod:UserAgent>
			<mod:ReceiverId>NDEAFIHH</mod:ReceiverId>
		</mod:RequestHeader>
		<mod:ApplicationRequest>{ApplicationRequest}</mod:ApplicationRequest>
	</cor:{Command}>

Sanomapohjat sisältävät muuttujamäärityksiä, jotka korvataan viestin rakennusvaiheessa. Esimerkiksi pohjassa esiintyvä `{ApplicationRequest}` korvataan varsinaisella `ApplicationRequest`-elementin sisällöllä.
