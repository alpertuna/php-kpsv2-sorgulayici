2017 KPS v2 PHP SOAP Sorgulayıcı
================================
Kps Sts sunucusundan kullanıcı adı şifre ile token alıp asıl sunucuya istek atan hafif ve kullanımı basit bir 
istemci kütüphanesi. `wsdl` dosyası kullanmadan direk manuel xml atıp almaya yarıyor. Ne kadar uzun uğraşsamda 
orjinal php soap kütüphanesine sts olayını anlatamadım o yüzden orjinal istemci sınıfını extend etmedim. 
Ama yapmak isteyen varsa pull request atabilir tabiki. Her türlü geliştirmeye açık.

## Örnek Kullanım
```php
require 'Kpsv2Sorgulayici.php';

$adresSorgu = 'https://kpsv2.nvi.gov.tr/Services/RoutingService.svc';
$adresSts   = 'https://kimlikdogrulama.nvi.gov.tr/Services/Issuer.svc/IWSTrust13';
$kullanici  = 'kullanici';
$sifre      = '*******';
$kps        = new Kpsv2Sorgulayici($adresSorgu, $adresSts, $kullanici, $sifre);

$metod      = 'http://kps.nvi.gov.tr/2011/01/01/BilesikKutukSorgulaKimlikNoServis/Sorgula';
$tcno       = 12345678901;
$xmlGovde = <<<XML
<Sorgula xmlns="http://kps.nvi.gov.tr/2011/01/01" xmlns:ns2="http://schemas.microsoft.com/2003/10/Serialization/">
  <kriterListesi>
    <BilesikKutukSorgulaKimlikNoSorguKriteri>
      <KimlikNo>$tcno</KimlikNo>
    </BilesikKutukSorgulaKimlikNoSorguKriteri>
  </kriterListesi>
</Sorgula>
XML;

$sonuc = $kps->calistir($metod, $xmlGovde);
print_r($sonuc);
// Gelen xmli object olarak kullanabilmek için gerekli kod bloğu 
$sonuc = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $sonuc);
$xml   = new SimpleXMLElement($sonuc);
echo '<pre>'; print_r($xml->sBody->SorgulaResponse->SorgulaResult->SorguSonucu->BilesikKutukBilgileri->TCVatandasiKisiKutukleri->NufusCuzdaniBilgisi);
```
