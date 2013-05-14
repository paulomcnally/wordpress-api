<?php
class Api{

	private $db;

	private $table_prefix;

	private $is_spanish = false;

	private $limit = 20;
	
	public function __construct( $database, $table_prefix ){
		$this->db = $database;
		$this->table_prefix = $table_prefix;
	}

	public function isSpanish(){
		$this->is_spanish = true;
	}

	public function getFeed(){
		$posts_formated = array();
		$query = "SELECT
				  P.ID AS id,
				  P.guid AS url,
				  P.post_date AS `date`,
				  P.post_title AS title,
				  P.post_content AS `text`,
				  U.display_name AS author
				FROM
				  ".$this->table_prefix."posts P,
				  ".$this->table_prefix."users U
				WHERE
				  P.post_status = 'publish'
				AND
				  P.post_type = 'post'
				AND
				  U.ID = P.post_author
				ORDER BY P.ID DESC
				LIMIT " . $this->limit;
		$posts = $this->db->get_results( $query  );
		if( count( $posts ) > 0 ){
			foreach( $posts as $post ){
				$category = $this->getCategoryByPost( $post->id );
				
				$post->category_id = $category->category_id;
				$post->category_name = $category->category_name;

				/*
				echo "<pre>";
				echo print_r( $post );
				echo "</pre>";
				die();
				*/

				array_push( $posts_formated, $post );
			}
		}

		$result = json_encode( $this->parse_data( $posts_formated ) );
		$result = str_replace('"image":null', '"image":""', $result);

		return $result;
	}

	public function getCategories(){
		$query = "SELECT term_id AS  id, name FROM ".$this->table_prefix."terms WHERE term_id IN( SELECT term_id FROM wp_term_taxonomy WHERE taxonomy = 'category' )";
		$result = json_encode( $this->db->get_results( $query ) );
		return $result;
	}

	private function getCategoryByPost( $post_id ){
		$query = "SELECT
  T.term_id as category_id,
  T.name AS category_name
FROM
  ".$this->table_prefix."term_relationships TR,
  ".$this->table_prefix."term_taxonomy TT,
  ".$this->table_prefix."terms T
WHERE
  TR.object_id = ". $post_id."
AND
  TT.term_taxonomy_id = TR.term_taxonomy_id
AND
  TT.taxonomy = 'category'
AND
  T.term_id = TT.term_id";
  		return end( $this->db->get_results( $query ) );
	}


	// Parse data object
	private function parse_data( $data ){
		$new_data = array();
		if( count( $data ) > 0 ){
			foreach( $data as $obj ){
				$new_obj = new stdClass();

				$new_obj->id = $obj->id;
				$new_obj->url = $obj->url;
				$new_obj->date = $this->parse_date( $obj->date );
				$new_obj->title = $this->clean_title( $obj->title );
				$new_obj->text = $this->clean_text( $obj->text );
				$new_obj->author = $obj->author;
				$new_obj->image = $this->getImage( $obj->text );
				$new_obj->category_id = $obj->category_id;
				$new_obj->category_name = $obj->category_name;

				array_push( $new_data, $new_obj );

			}
		}
		return $new_data;

	}


	// Parse date
	private function parse_date( $date ){
		$day = date( "j", strtotime( $date ) );
		$month = date( "F", strtotime( $date ) );
		$year = date( "Y", strtotime( $date ) );

		if( $this->is_spanish ){
			$month = "de " . $this->date_month_es( $month );
		}
		return $day. " " . $month . "," . $year;
		
	}

	// Espanish format month date
	private function date_month_es( $en_month ){
		$en = array("January","February","March","April","May","June","July","August","September","October","November","December");
		$es = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
		$es_month = "";
		foreach( $en as $index=>$name ){
			if( $name == $month ){
				$es_month = $es[$index];
			}
		}
		return $es_month;
	}

	private function clean_title( $title ){
		return str_replace("'","",$title);
	}

	private function clean_text( $text ){
		$text = preg_replace("/\[caption.*\[\/caption\]/", '', $text);
		$text = str_replace("'","",$text);
		$text = strip_tags( $text );
		$text = preg_replace(array("/\r\n\r\n/", "/\n/"), array("\r\n", "\n"), $text);
		$text = str_replace("\n\t", "\n", $text);
		$text = str_replace("\r\n", "\n", $text);
		$text = str_replace("\n \n", "\n", $text);
		return $text;
	}

	private function getImage( $text ){
		$dom = new domDocument;
		@$dom->loadHTML( $text );
		@$dom->preserveWhiteSpace = false;
		@$images = $dom->getElementsByTagName('img');
		if( count( $images ) > 0 ){
			foreach($images as $img){
				return $img->getAttribute('src');
			}
		}
		else{
			return "";
		}
	}


}
?>