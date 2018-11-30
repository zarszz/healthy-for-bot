<?php
require __DIR__ . '/vendor/autoload.php';
 
use \LINE\LINEBot;
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use \LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselTemplateBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateBuilder\CarouselColumnTemplateBuilder;
use \LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use \LINE\LINEBot\TemplateActionBuilder\UriTemplateActionBuilder;
use \LINE\LINEBot\SignatureValidator as SignatureValidator;
 
// set false for production
$pass_signature = true;
 
// set LINE channel_access_token and channel_secret
$channel_access_token = "YOUR_ACCES_TOKEN_HERE";
$channel_secret = "YOUR_CHANNEL_SECRET_HERE";
 
//initialize line bot object
$httpClient = new CurlHTTPClient($channel_access_token);
$bot = new LINEBot($httpClient, ['channelSecret' => $channel_secret]);
 
$configs =  ['settings' => ['displayErrorDetails' => true],];
$app = new Slim\App($configs);
 
//create route for default homepage
$app->get('/', function($req, $res)
{
  echo "hello, world !!";
});

$app->get('/content/{messageId}', function($req, $res) use ($bot)
{
    // get message content
    $route      = $req->getAttribute('route');
    $messageId = $route->getArgument('messageId');
    $result = $bot->getMessageContent($messageId);
 
    // set response
    $res->write($result->getRawBody());
 
    return $res->withHeader('Content-Type', $result->getHeader('Content-Type'));
});

 
// create route for webhook
$app->post('/webhook', function ($request, $response) use ($bot, $pass_signature, $httpClient)
{
    // get request body and line signature header
    $body        = file_get_contents('php://input');
    $signature = isset($_SERVER['HTTP_X_LINE_SIGNATURE']) ? $_SERVER['HTTP_X_LINE_SIGNATURE'] : '';
 
    // log body and signature
    file_put_contents('php://stderr', 'Body: '.$body);
 
    if($pass_signature === false)
    {
        // is LINE_SIGNATURE exists in request header?
        if(empty($signature))
        {
            return $response->withStatus(400, 'Signature not set');
        }
 
        // is this request comes from LINE?
        if(! SignatureValidator::validateSignature($body, $channel_secret, $signature))
        {
            return $response->withStatus(400, 'Invalid signature');
        }
    } //end if
 
    $data = json_decode($body, true);
    if(is_array($data['events'])){
        foreach ($data['events'] as $event){
                //message from user type set 
                $textmessageFromUser = $event['message']['text']; 

                if(strtolower($textmessageFromUser) == "help"){
                    $multiMessageBuilder = new MultiMessageBuilder();
                    $multiMessageBuilder->add(new TextMessageBuilder('Kenapa olahraga?(ketikan olahraga)', 'Kenapa tidur?(ketikan tidur)','Manfaat buah-buahan(ketikan buah)', 'Mengapa minum air putih ?(air putih)'));
                    $result = $bot->replyMessage($event['replyToken'], $multiMessageBuilder);
                    return $response->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
                }//end if

                if(strtolower($textmessageFromUser) == "tidur"){
                    // template flex message location
                    $flexTemplate = file_get_contents("tidur.json"); 
                	$result = $httpClient->post(LINEBot::DEFAULT_ENDPOINT_BASE . '/v2/bot/message/reply',                                                      
                        [
                            'replyToken' => $event['replyToken'],
                             'messages'   => [
                                [
                                    'type'     => 'flex',
                                    'altText'  => 'manfaat tidur',
                                    'contents' => json_decode($flexTemplate)
                                ]
                            ],
                        ]);
                    return $response->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());                    
                }//end if
                
                if(strtolower($textmessageFromUser) == "olahraga"){
                    // template flex message location
                    $flexTemplate = file_get_contents("olahraga.json"); 
                    $result = $httpClient->post(LINEBot::DEFAULT_ENDPOINT_BASE . '/v2/bot/message/reply', 
                        [
                            'replyToken' => $event['replyToken'],
                             'messages'   => [
                                [
                                    'type'     => 'flex',
                                    'altText'  => 'manfaat olahraga',
                                    'contents' => json_decode($flexTemplate)
                                ]
                            ],
                        ]);
                    return $response->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());                    
                }//end if

                if(strtolower($textmessageFromUser) == "air putih"){
                    //template flex location
                    $flexTemplate = file_get_contents("airPutih.json"); 
                    $result = $httpClient->post(LINEBot::DEFAULT_ENDPOINT_BASE . '/v2/bot/message/reply', 
                        [
                            'replyToken' => $event['replyToken'],
                             'messages'   => [
                                [
                                    'type'     => 'flex',
                                    'altText'  => 'manfaat air putih',
                                    'contents' => json_decode($flexTemplate)
                                ]
                            ],
                        ]);
                    return $response->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());   
                }//end if

                if(strtolower($textmessageFromUser) == "buah"){
                    //set the carousel template
                    $carouselTemplateBuilder = new CarouselTemplateBuilder([
                        new CarouselColumnTemplateBuilder("Apel", "Apel sangat bermanfaat bagi tubuh, seperti sebagai antioksidan, menetralisir racun dalam butuh, kemudian karena apel kaya akan serat sehingga dapat mengurangi tinkat kolestrol,diare dan sembelit.","https://i.ibb.co/MnmMzfr/apel.jpg",
                    [
                        new UriTemplateActionBuilder('lebih lanjut',"https://cerpin.com/alasan-penting-untuk-makan-apel/"),
                    ]),
                        new CarouselColumnTemplateBuilder("Jeruk", "Jeruk adalah salah satu buah yang memiliki kandungan vitamin C yang tinggi, sehingga dapat menjadi sistem imun, memantau diet, mencegah kanker, hingga mencegah kerusakan kulit.","https://i.ibb.co/g43x0ZD/jeruk.jpg",
                    [
                        new UriTemplateActionBuilder('lebih lanjut',"https://manfaat.co.id/25-manfaat-buah-jeruk-untuk-kesehatan-dan-kecantikan"),
                    ]),
                    ]);
                    $templateMessage = new TemplateMessageBuilder('manfaat buah-buahan', $carouselTemplateBuilder);
                    $result = $bot->replyMessage($event['replyToken'], $templateMessage);
                    return $response->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());                    
                }//end if

                else{
                    $multiMessageBuilder = new MultiMessageBuilder();
                    $multiMessageBuilder->add(new TextMessageBuilder('maaf, pesan tersebut belum didukung'));
                    $result = $bot->replyMessage($event['replyToken'], $multiMessageBuilder);
                    return $response->withJson($result->getJSONDecodedBody(), $result->getHTTPStatus());
                }
            }//end for
    } // end if

    //when events not set
	return $response->withStatus(400, 'No event sent!');
});
 
$app->run(); //EOF
