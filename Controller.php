<?php 
    require_once ( 'simple_html_dom.php' );

    function get_dom_single_page($url){
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

        $options = array(

            CURLOPT_CUSTOMREQUEST => "GET",        //set request type post or get
            CURLOPT_POST => false,        //set to GET
            CURLOPT_USERAGENT => $user_agent, //set user agent
            CURLOPT_COOKIEFILE => "cookie.txt", //set cookie file
            CURLOPT_COOKIEJAR => "cookie.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING => "",       // handle all encodings
            CURLOPT_AUTOREFERER => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT => 120,      // timeout on response
            CURLOPT_MAXREDIRS => 10,       // stop after 10 redirects
        );

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);

        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['content'] = $content;
        return $header['content'];
    }

    function get_list_url($url, $elemment){

        $html = get_dom_single_page($url);

        $html = str_get_html($html);

        $get = $elemment . ' a';

        $url_arr = array();

        foreach ($html->find($get) as $key => $dom) {

            $url = $dom->href;

            $url_arr[] = array(
                'url' => $url,
                'id'  => $dom->getAttribute('data-id')
            );
        }
        return $url_arr;
    }
    function preg_data($content , $elm){

        $content = preg_replace('([\s]+)', ' ', $content);

        $content = strip_tags($content);
    
        $content = trim($content);

        $pattent = '/'.$elm.'\s=\s(.+?)?;/m';
        
        preg_match( $pattent , $content , $matches );
        
        return (isset($matches) && !empty($matches)) ? $matches[1] : flase;

    }

    function remove_white_space($content){
        $content = preg_replace('([\s]+)', ' ', $content);
        return $content;
    }

    function get_data_tiki($url){
        $html = get_dom_single_page($url);
        $html = str_get_html($html);
        $scripts = $html->find('script');
        $script = "";
        foreach($scripts as $s) {
            if(strpos($s->innertext, 'defaultProduct') !== false) {
                $script = $s;
            }
        }

        // echo $script;

        $iamges = preg_data($script,'images');

        json_decode($iamges , true) ? $iamges = json_decode($iamges , true) : $iamges = [];


        // get shop

        $shop = preg_data($script , 'currentSeller');
        json_decode($shop , true) ? $shop = json_decode($shop , true) : $shop = [];

        // get product

        $product = preg_data($script , 'defaultProduct');
        json_decode($product , true) ? $product = json_decode($product , true) : $product = [];
        $brand = $html->find('.item-brand p a', 0);
        $brand = array(
            'name' => $brand->plaintext,
            'url'  => $brand->href  
        );

        // product desc 

        $desc = $html->find('#gioi-thieu' , 0);
        $desc = remove_white_space($desc);
        $desc = html_entity_decode($desc);

        // detail product

        $detail = $html->find('#chi-tiet' , 0);
        $detail = remove_white_space($detail);
        $detail = html_entity_decode($detail);

        // echo $html;


        $product = array(
            'images' => $iamges,
            'shop'   => $shop,
            'brand'  => $brand,
            'product'=> $product,
            'desc'   => $desc,
            'detail' => $detail
        );
        return $product;
    }

    function run(){
        $url = "https://tiki.vn/dien-tu-dien-lanh/c4221?_lc=Vk4wMzQwMjAwMDc%3D&src=c.4221.hamburger_menu_fly_out_banner";
        $arr = get_list_url($url,'.product-item');
        $products = [];
        if(isset($arr) && !empty($arr)){
            foreach ($arr as $key => $item) {
                $product = get_data_tiki($item['url']);
                $products[] = $product;
            }
        }

        echo '<pre>';
        print_r($products);
        echo '</pre>';
        return $products;


    }

    // get_data_tiki("https://tiki.vn/ipad-10-2-inch-wifi-32gb-new-2019-hang-nhap-khau-chinh-hang-p32648365.html?src=recently-viewed&spid=32648373");

    run();
?>