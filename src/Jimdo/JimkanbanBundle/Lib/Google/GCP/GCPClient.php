<?php
namespace Jimdo\JimkanbanBundle\Lib\Google\GCP;

use \Jimdo\JimkanbanBundle\Lib\Google\GoogleClient;

class GCPClient
{
    /**
     * @var \Jimdo\JimkanbanBundle\Lib\Google\GoogleClient
     */
    private $client;

    /**
     * @param \Jimdo\JimkanbanBundle\Lib\Google\GoogleClient $client
     */
    public function __construct(GoogleClient $client)
    {
        $this->client = $client;
    }
    /**
     * @return string
     */
    protected function getAccountType()
    {
        return self::ACCOUNT_TYPE;
    }

    /**
     * @return string
     */
    protected function getSource()
    {
        return self::SOURCE;
    }

    /**
     * @return string
     */
    protected function getServiceName()
    {
        return self::SERVICE_NAME;
    }

    /**
     * @return \Buzz\Message\Response
     */
    public function getPrinterList()
    {
        $response = $this->client->get('http://www.google.com/cloudprint/search');
        $this->assertRequestIsSuccessful($response);

        $json = $this->getJson($response);

        return $json['printers'];
    }

    public function getPrinterInformation($printerId)
    {
        return $this->client->get('http://www.google.com/cloudprint/printer?printerid=' . $printerId);
    }


    /**
     * @param $printerId
     * @param array $configuration
     * @return #M#C\Jimdo\JimkanbanBundle\Lib\Google\GCP\GCPClient.post|?
     */
    public function submitPrintJob($printerId, array $configuration)
    {
        //XXX Default caps for our Epson needs to replaced with some kind of configuration
        $capabilities = '{
  "capabilities": [
    {
     "name": "psk:PageScaling",
     "type": "Feature",
     "psf:SelectionType": "psk:PickOne",
     "psk:DisplayName": "Reduce/Enlarge",
     "options": [
      {
       "name": "psk:None",
       "default": true,
       "psk:DisplayName": "Actual Size"
      }
     ]
    },
    {
     "name": "psk:JobCopiesAllDocuments",
     "type": "ParameterDef",
     "value": "1",
     "psf:Mandatory": "psk:Unconditional",
     "psf:Multiple": "1",
     "psf:DataType": "xsd:integer",
     "psf:MinValue": "1",
     "psf:UnitType": "copies",
     "psk:DisplayName": "Copies",
     "psf:DefaultValue": "1",
     "psf:MaxValue": "99"
    },
    {
     "name": "psk:JobDuplexAllDocumentsContiguously",
     "type": "Feature",
     "psf:SelectionType": "psk:PickOne",
     "psk:DisplayName": "2-Sided Printing",
     "options": [
      {
       "name": "psk:OneSided",
       "default": true,
       "psk:DisplayName": "Off"
      }
     ]
    },
    {
     "name": "psk:JobPageOrder",
     "type": "Feature",
     "psf:SelectionType": "psk:PickOne",
     "psk:DisplayName": "Reverse Order",
     "options": [
      {
       "name": "psk:Reverse",
       "default": true,
       "psk:DisplayName": "On"
      }
     ]
    },
    {
     "name": "psk:PageInputBin",
     "type": "Feature",
     "psf:SelectionType": "psk:PickOne",
     "psk:DisplayName": "Paper Source",
     "options": [
      {
       "name": "psk:AutoSelect",
       "default": true,
       "psk:DisplayName": "Auto"
      }
     ]
    },
    {
     "name": "psk:PageMediaSize",
     "type": "Feature",
     "psf:SelectionType": "psk:PickOne",
     "psk:DisplayName": "Paper Size",
     "options": [
      {
       "name": "epns200:Fullsize4x6",
       "psk:DisplayName": "A6",
       "scoredProperties": {
        "psk:MediaSizeWidth": "101600",
        "psk:MediaSizeHeight": "152400"
       }
      }
     ]
    },
    {
     "name": "psk:PageMediaType",
     "type": "Feature",
     "psf:SelectionType": "psk:PickOne",
     "psk:DisplayName": "Paper Type",
     "options": [
      {
       "name": "psk:Plain",
       "default": true,
       "psk:DisplayName": "plain papers"
      }
     ]
    },
    {
     "name": "psk:PageResolution",
     "type": "Feature",
     "psf:SelectionType": "psk:PickOne",
     "psk:DisplayName": "Quality",
     "options": [
      {
       "name": "epns200:Level2",
       "default": true,
       "psk:DisplayName": "Standard",
       "scoredProperties": {
        "psk:ResolutionX": "300",
        "psk:ResolutionY": "300"
       }
      }
     ]
    },
    {
     "name": "psk:PageOrientation",
     "type": "Feature",
     "psf:SelectionType": "psk:PickOne",
     "psk:DisplayName": "Orientation",
     "options": [
      {
       "name": "psk:ReverseLandscape",
       "psk:DisplayName": "Landscape"
      }
     ]
    },
    {
     "name": "psk:PageOutputColor",
     "type": "Feature",
     "psf:SelectionType": "psk:PickOne",
     "psk:DisplayName": "Color",
     "options": [
      {
       "name": "psk:Color",
       "default": true,
       "psk:DisplayName": "Color"
      }
     ]
    }
   ]
}
';
        //XXX Use mime
        $data = array(
            'printerid' => $printerId,
            'content' => base64_encode($configuration['content']),
            'contentType' => 'application/pdf',//$configuration['mime'],
            'capabilities' => $capabilities,
            'title' => 'Ticket',
            'tag' => 'ticket',
            'contentTransferEncoding' => 'base64'
        );

        $response = $this->client->post('http://www.google.com/cloudprint/submit', array(), $data);
        $this->assertRequestIsSuccessful($response);

        return $this->getJson($response);
    }

    private function getJson(\Buzz\Message\Response $response)
    {
        $json = json_decode($response->getContent(), true);

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \Exception('Huh, could not parse JSON :( : ' . $response->getContent());
        }

        return $json;
    }

    private function assertRequestIsSuccessful(\Buzz\Message\Response $response)
    {
        $data = json_decode($response->getContent(), true);

        if (!$response->isSuccessful() || !isset($data['success']) || $data['success'] == false) {
            //XXX Invent Exception fitting here
            throw new \InvalidArgumentException($response->getContent());
        }
    }


}
