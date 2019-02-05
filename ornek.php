<?php

/**
 * File: ornek.php
 * @author H.Alper Tuna <halpertuna@gmail.com>
 * Date: 15.07.2017
 * Last Modified Date: 15.07.2017
 * Last Modified By: H.Alper Tuna <halpertuna@gmail.com>
 */

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
// Gelen xml çıktısını objeye çeviren kod bloğu 
$sonuc = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $sonuc);
$xml   = new SimpleXMLElement($sonuc);
echo '<pre>'; print_r($xml->sBody->SorgulaResponse->SorgulaResult->SorguSonucu->BilesikKutukBilgileri->TCVatandasiKisiKutukleri->NufusCuzdaniBilgisi);