<?php
namespace App\Library\Services;
use App\Library\Services\FlipkartAPI;
/**
 * Class FlipkartApp hits particular shopify apis with required credits
 */
class FlipkartApp
{
    private $_API = array();
    /**
	 * Checks for presence of setup $data array and loads
	 * @param bool $data
	 */
	public function __construct($data = FALSE)
	{
		if (is_array($data))
		{
		      $this->setup($data);
		}

	}
    /**
     * Returns Flipkart connection, filters provided data, and loads into $this->_API
     * @param array $data
     */
    public function setup($data = array())
    {
        $this->_API = new FlipkartAPI(['API_KEY' => $data['API_KEY'], 'API_SECRET' => $data['API_SECRET'], 'API_URL' => $data['API_URL']]);
        $this->_API->getAccessToken();
    }
    /**
    *****************************************************************************
    *****************************************************************************
    *    ORDER APIS FROM HERE
    *****************************************************************************
    *****************************************************************************
    **/

    /**
     * Calls API and returns Order JSON, single order in case of order no as argument
     * @param string $id
     * @return json
     */
    public function getOrder($fd=NULL,$td=NULL){
        try
        {
            $body=['filter'=>['orderDate'=>['fromDate'=>$fd,'toDate'=>$td]]];
            $resp=$this->_API->call(['URL' => '/sellers/v2/orders/search', 'METHOD' => 'POST','DATA'=>$body,'ALLDATA'=>true,'RETURNARRAY'=>true]);
            $result = isset($resp['orderItems'])?$resp['orderItems']:false;
            if($result){
                if($resp['hasMore']){
                    while (isset($resp['hasMore']) && ($resp['hasMore'] == true)) {
                        $resp=$this->_API->call(['URL' => $resp['nextPageUrl'], 'METHOD' => 'GET','ALLDATA'=>true,'RETURNARRAY'=>true]);
                        if(isset($resp['orderItems'])){
                            $result = array_merge($result,$resp['orderItems']);
                        }
                    }
                }
            }
        }
        catch (Exception $e)
        {
            $result = $e->getMessage();
        }
        return $result;
    }

    /**
    * Calls API to get order details for order item Ids 
    * @param string $orderItemIds Comma separated order item Ids
    * @return json
    */
    public function getOrderDetails($orderItemIds){
        try {
                if($orderItemIds){
                    $result=$this->_API->call(['URL'=>'/sellers/v2/orders/shipments?orderItemIds='.$orderItemIds, 'METHOD' => "GET",'RETURNARRAY'=>true]);
                    return $result;
                }
        } catch (Exception $e) {
            
        }
    }


    /**
        * Calls API and Update  product inventory
        * @param string $id
        * @param array $data
        *         {
        *           "<sku>": {
        *               "product_id": "<product_id>",
        *               "locations": [
        *                   {
        *                   "id": "<location-id>",
        *                   "inventory": 0
        *               }
        *               ]
        *           }
        *       }
        *   $locations=[['id'=>$location_id,'inventory'=>$count]];
        *   $data=[$sku=>['product_id'=>$product_id,'locations'=>$locations]];
        * @return json
        */
    public function updateInventory($data){
        try
        {
            $result=array();
            if($data){
                $result=$this->_API->call(['URL'=>'/sellers/listings/v3/update/inventory', 'METHOD' => "POST",'DATA'=>$data,'ALLDATA'=>true,'RETURNARRAY'=>true]);
            }
        }
        catch (Exception $e)
        {
        	$result = $e->getMessage();
        }

        return $result;
    }
    /**
      * Calls API and get sku details
      * @param string $sku comma seprated sku's max 10
      * @return array
      */
    public function getSkudetail($sku=NULL){
        try
        {
            $result=array();
            if($sku){
                $res=$this->_API->call(['URL'=>'/sellers/listings/v3/'.$sku, 'METHOD' => "GET",'RETURNARRAY'=>true]);
                foreach ($res['available'] as $key => $value) {
                    $result[$key]['product_id']=$value['product_id'];
                    $result[$key]['listing_id']=$value['listing_id'];
                }
            }
        }
        catch (Exception $e)
        {
        	$result = $e->getMessage();
        }

        return $result;
    }
  /**
    * Calls API and Update  product inventory directly from sku
    * @param string $sku comma seprated sku's max 10
    * @param string $location_id location id for flipkart
    * @return json
    */
    public function updateApiInventory($sku=NULL,$location_id,$qty){
        try
        {
            $result=array();
            if($sku){
                $detail=$this->getSkudetail($sku);
                $locations=[['id'=>$location_id,'inventory'=>$qty]];
                $data=array();
                foreach ($detail as $key => $value) {
                    $data[$key]=['product_id'=>$value['product_id'],'locations'=>$locations];
                }
                $result=$this->_API->call(['URL'=>'/sellers/listings/v3/update/inventory', 'METHOD' => "POST",'DATA'=>$data,'ALLDATA'=>true,'RETURNARRAY'=>true]);
            }
        }
        catch (Exception $e)
        {
        	$result = $e->getMessage();
        }

        return $result;
    }
    
}
