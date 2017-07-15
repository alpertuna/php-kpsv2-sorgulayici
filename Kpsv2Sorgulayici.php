<?php
/**
 * File: Kpsv2Sorgulayici.php
 * @author H.Alper Tuna <halpertuna@gmail.com>
 * Date: 15.07.2017
 * Last Modified Date: 15.07.2017
 * Last Modified By: H.Alper Tuna <halpertuna@gmail.com>
 */

class Kpsv2Sorgulayici {
  private $adresSorgu;
  private $adresSts;
  private $kullanici;
  private $sifre;

  // -------------------------------------------------------------------------------------------
  // YARDIMCI METODLAR
  // -------------------------------------------------------------------------------------------
  public function __construct($adresSorgu, $adresSts, $kullanici, $sifre) {
    $this->adresSorgu = $adresSorgu;
    $this->adresSts = $adresSts;
    $this->kullanici = $kullanici;
    $this->sifre = $sifre;
  }
  private function getZamanDamgasi($aralik = 0) {
      return gmdate("Y-m-d\TH:i:s\Z", time() + $aralik);
  }
  private function getXmlZamanDamgasiBasligi() {
    $zdBasla = $this->getZamanDamgasi();
    $zdBitis = $this->getZamanDamgasi(300);
    return <<<XML
<wsu:Timestamp xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd" wsu:Id="_0">
  <wsu:Created>$zdBasla</wsu:Created>
  <wsu:Expires>$zdBitis</wsu:Expires>
</wsu:Timestamp>
XML;
  }
  private function getXmlSecurityBasligi($icerik1, $icerik2, $adres, $metod) {
    return <<<XML
<wsse:Security>
  $icerik1
  $icerik2
</wsse:Security>
<wsa:To>$adres</wsa:To>
<wsa:Action>$metod</wsa:Action>
XML;
  }
  private function getXmlSoapTam($baslik, $govde) {
    return <<<XML
<s:Envelope
  xmlns:s="http://www.w3.org/2003/05/soap-envelope"
  xmlns:wsa="http://www.w3.org/2005/08/addressing"
  xmlns:dsig="http://www.w3.org/2000/09/xmldsig#"
  xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd"
  xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"
  xmlns:wst="http://docs.oasis-open.org/ws-sx/ws-trust/200512"
  xmlns:b="http://docs.oasis-open.org/wss/oasis-wss-wssecurity-secext-1.1.xsd"
>
  <s:Header>$baslik</s:Header>
  <s:Body>$govde</s:Body>
</s:Envelope>
XML;
  }
  private function soapSorguYap($adres, $xml) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $adres);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/soap+xml; charset=utf-8',
    ));
    $sonuc = curl_exec($ch);
    curl_close($ch);
    return $sonuc;
  }

  // -------------------------------------------------------------------------------------------
  // ASIL SORGU METODU
  // -------------------------------------------------------------------------------------------
  public function calistir($anaMetod, $anaXmlGovde) {
    // Sts den kullanıcı şifre ile authentice olup token alma aşması
    // -------------------------------------------------------------------------------------------
    $xmlZd = $this->getXmlZamanDamgasiBasligi();
    $xmlSecurityIcerik = <<<XML
<wsse:UsernameToken wsu:Id="Me">
  <wsse:Username>$this->kullanici</wsse:Username>
  <wsse:Password>$this->sifre</wsse:Password>
</wsse:UsernameToken>
XML;
    $xmlBaslik = $this->getXmlSecurityBasligi($xmlZd, $xmlSecurityIcerik, $this->adresSts, 'http://docs.oasis-open.org/ws-sx/ws-trust/200512/RST/Issue');

    $tokenTipi = 'http://docs.oasis-open.org/wss/oasis-wss-saml-token-profile-1.1#SAMLV1.1';
    $keyTipi = 'http://docs.oasis-open.org/ws-sx/ws-trust/200512/SymmetricKey';

    $xmlGovde = <<<XML
<wst:RequestSecurityToken>
  <wst:TokenType>$tokenTipi</wst:TokenType>
  <wst:RequestType>http://docs.oasis-open.org/ws-sx/ws-trust/200512/Issue</wst:RequestType>
  <wsp:AppliesTo xmlns:wsp="http://schemas.xmlsoap.org/ws/2004/09/policy">
    <wsa:EndpointReference>
      <wsa:Address>$this->adresSorgu</wsa:Address>
    </wsa:EndpointReference>
  </wsp:AppliesTo>
  <wst:KeyType>$keyTipi</wst:KeyType>
</wst:RequestSecurityToken>
XML;

    $xmlSorgu = $this->getXmlSoapTam($xmlBaslik, $xmlGovde);
    $sonuc = $this->soapSorguYap($this->adresSts, $xmlSorgu);

    // Alınan tokenı imzalama aşaması
    // -------------------------------------------------------------------------------------------
    $dom = new DOMDocument();
    $dom->loadXML($sonuc);
    $doc = $dom->documentElement;
    $xpath = new DOMXpath($dom);
    $xpath->registerNamespace('s', 'http://www.w3.org/2003/05/soap-envelope');
    $xpath->registerNamespace('wst', 'http://docs.oasis-open.org/ws-sx/ws-trust/200512');
    $xpath->registerNamespace('wsse', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
    $xpath->registerNamespace('trust', 'http://docs.oasis-open.org/ws-sx/ws-trust/200512');
    $xpath->registerNamespace('o', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
    $token = $xpath->query('/s:Envelope/s:Body/wst:RequestSecurityTokenResponseCollection/wst:RequestSecurityTokenResponse/wst:RequestedSecurityToken', $doc);
    $proofKey = $xpath->query('/s:Envelope/s:Body/wst:RequestSecurityTokenResponseCollection/wst:RequestSecurityTokenResponse/wst:RequestedProofToken/wst:BinarySecret', $doc);
    $samlAssignID = $xpath->query('/s:Envelope/s:Body/trust:RequestSecurityTokenResponseCollection/trust:RequestSecurityTokenResponse/trust:RequestedAttachedReference/o:SecurityTokenReference/o:KeyIdentifier', $doc);

    $proofKey = base64_decode($proofKey->item(0)->textContent);
    $token = $dom->saveXML($token->item(0)->firstChild);
    $samlAssignID = $samlAssignID->item(0)->textContent;

    $xmlZd = $this->getXmlZamanDamgasiBasligi();

    $dom = new DOMDocument();
    $dom->loadXML($xmlZd);
    $canonicalXML = $dom->documentElement->C14N(TRUE, FALSE);
    $digestValue = base64_encode(hash('sha1', $canonicalXML, TRUE));
    $signedInfo = <<<XML
<dsig:SignedInfo xmlns:dsig="http://www.w3.org/2000/09/xmldsig#">
  <dsig:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
  <dsig:SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#hmac-sha1"/>
  <dsig:Reference URI="#_0">
    <dsig:Transforms><dsig:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"></dsig:Transform></dsig:Transforms>
    <dsig:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1"/>
    <dsig:DigestValue>$digestValue</dsig:DigestValue>
  </dsig:Reference>
</dsig:SignedInfo>
XML;
    $dom = new DOMDocument();
    $dom->loadXML($signedInfo);
    $canonicalXml = $dom->documentElement->C14N(TRUE, FALSE);
    $signatureValue = base64_encode(hash_hmac('sha1', $canonicalXml , $proofKey, TRUE));
    $tokenImza = <<<XML
<dsig:Signature>
  $signedInfo
  <dsig:SignatureValue>$signatureValue</dsig:SignatureValue>
  <dsig:KeyInfo>
    <wsse:SecurityTokenReference b:TokenType="http://docs.oasis-open.org/wss/oasis-wss-saml-token-profile-1.1#SAMLV1.1">
      <wsse:KeyIdentifier ValueType="http://docs.oasis-open.org/wss/oasis-wss-saml-token-profile-1.0#SAMLAssertionID">$samlAssignID</wsse:KeyIdentifier>
    </wsse:SecurityTokenReference>
  </dsig:KeyInfo>
</dsig:Signature>
XML;

    // Token kullanarak asıl sorguyu yapma aşaması
    // -------------------------------------------------------------------------------------------
    $xmlBaslik = $this->getXmlSecurityBasligi($xmlZd, $token . $tokenImza, $this->adresSorgu, $anaMetod);
    $xmlSorgu = $this->getXmlSoapTam($xmlBaslik, $anaXmlGovde);
    return $this->soapSorguYap($this->adresSorgu, $xmlSorgu);
  }
}
