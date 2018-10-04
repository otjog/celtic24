<?php

namespace App\Http\Controllers\Shop;

use App\Models\Shop\Product\Product;
use App\Models\Shop\Order\Basket;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Settings;

class BasketController extends Controller{

    protected $baskets;

    protected $data;

    public function __construct(Basket $baskets){

        $settings = Settings::getInstance();

        $this->data = $settings->getParameters();

        $this->baskets = $baskets;

        $this->data['template'] = [
            'component' => 'shop',
            'resource'  => 'basket'
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request){

        $token = $request->session()->get('_token');

        $basket = $this->baskets->getActiveBasket( $token );

        $parameters = $request->all();

        unset($parameters['_token']);

        if($basket === null){

            dd($parameters);

            $this->baskets->token     = $token;

            $this->baskets->products_json  = json_encode( [ $request->all() ] );

            $this->baskets->save();

        }else{
            $products = $this->addProductToArray( $basket, $request->all() );

            $basket->products_json = json_encode($products);

            $basket->save();
        }

        return back();
    }

    /**
     * Display the specified resource.
     *
     *
     * @return \Illuminate\Http\Response
     */
    public function show($token){


    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $products, $token){

        $basket = $this->baskets->getActiveBasketWithProducts( $products, $token );

        if($basket->order_id === null){

            $this->data['template']['view'] = 'edit';

            $this->data['data']['basket']   = $basket;

            $this->data['data']['parcels'] = $products->getParcelParameters($basket->products);

            return view( 'templates.default', $this->data);

        } else {

            return redirect('orders.show', $basket->order_id);

        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $token){

        $basket = $this->baskets->getActiveBasket( $token ) ;

        $products = $this->changeQuantityInArray( $request->all() );

        $basket->products_json = json_encode($products);

        $basket->save();

        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    private function changeQuantityInArray($products){

        $newProducts = [];

        foreach ($products as $key => $product){

            if( substr( $key, 0, 1 ) !== '_'){

                $product['quantity'] = (int)$product['quantity'];

                if($product['quantity'] > 0){

                    $newProducts[] = $product;

                }

            }

        }

        return $newProducts;
    }

    private function addProductToArray($basket, $newProduct){

        $products = json_decode($basket->products_json, true);

        $is_new = false;

        foreach($products as $index => $product){

            if( isset($newProduct) ){

                foreach ($product as $key => $value){

                    if( $key !== 'quantity' ){

                        if( $product[ $key ] !== $newProduct[ $key ] ){

                            $is_new = true;

                        }

                    }

                }

            }

        }

        if($is_new){

            $products[] = $newProduct;

        }else{

            $quantity = $product['quantity']*1 + $newProduct['quantity']*1;

            $products[$index]['quantity'] = (string) $quantity;
        }

        return $products;
    }
}
