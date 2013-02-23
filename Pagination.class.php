<?php

class Pagination
{
	/**
	* Constructs the pagination object in which is the list that contains the links
	*
	* @param array $args Array of arguments. Defaults may be seen below in __construct()
	*
	* @return object The only real use for the return object are the links, i.e., if you instantiate $pagination = new Pagination(); you would echo $pagination->links
	*				 Public methods include (static) get_offset(), result_per_page_form(), and (static) get_rows_per_page(). The two static methods may be useful for 
	*				 your pagination query.
	*/	
	function __construct( $args = array() )
	{
		$defaults = array(
			'total_records'			=> 0,
			'visible_page_numbers'	=> 0,
			'default_rows_per_page'	=> 10,
			'max_rows_per_page'		=> 99,
			'page_get_var'			=> 'page',
			'perpage_get_var'		=> 'perpage',
			'style'					=> false,
			'class'					=> '',
			'link_text'				=> array(
				'next'	=> 'Next',
				'prev'	=> 'Previous',
				'first'	=> '&#171;',
				'last'	=> '&#187;'
			)
		);
				
		$args = array_merge( $defaults, $args );
		$this->args					= $args;
		$this->rows_per_page 		= $this->get_rows_per_page();
		$this->current_page			= $this->get_current_page();
		$this->prev_page			= $this->current_page -1;
		$this->next_page			= $this->current_page +1;
		$this->last_page 			= ceil( $this->args['total_records'] / $this->rows_per_page  );
		$this->links				= $this->links();
	}
	
	/**
	* Get the offset for a paginated mysql query
	*
	* @return int
	*/
	public static function get_offset()
	{
		$offset	= ( self::get_current_page() - 1 ) * self::get_rows_per_page();
		return $offset;
	}
	
	/**
	* Display a results per page form
	*
	* @param array $args Array that serves as the defaults.
	*
	* @return string The html that produces the div and form
	*/
	public function results_per_page_form( $args = array() )
	{
		$defaults = array(
			'total_records'	=> 0,
			'action'		=> basename( $_SERVER['PHP_SELF'] ),
			'method'		=> 'GET',
			'options'		=> array(10,20,30,40,50),
			'class'			=> '',
			'submit_text'	=> 'Update',
			'label'			=> 'Results per page: '
		);
		
		$args = array_merge( $defaults , $args );
		
		if ( $args['total_records'] > $args['options'][0] )
		{
			$form = '<div class="results-per-page '. $args['class'] .'"><form action="'. $args['action'] .'" method="'. $args['method'] .'">';
			foreach ( $_GET as $param => $value )
			{	
				if ( $param == $this->args['perpage_get_var'] || $param == $this->args['page_get_var'] ) continue;
				$form .= '<input type="hidden" name="'. $param .'" value="'. $value .'">';
			}
			if ( $args['label'] )
			{
				$form.=	'<label for="perpage">'. $args['label'] .'</label>';
			}
			$form .= '<select name="'. $this->args['perpage_get_var'] .'">';
					foreach ( $args['options'] as $num )
					{
						if ( $_GET[$this->args['perpage_get_var']] == $num )
						{
							$form .= '<option selected="selected" value="'. $num .'">'. $num .'</option>';
						}
						else
						{
							$form .= '<option value="'. $num .'">'. $num .'</option>';
						}
					}
					if ( $args['submit_text'] )
					{
						$form .= '<input type="submit" value="'. $args['submit_text'] .'">';
					}
					$form .= '
				</select>
			</form></div>';
			return $form;
		}
		else return null;
	}
	
	/**
	* Gets the rows per page
	*
	* @return int Returns $_GET['perpage'] if it's set, numeric, and <= $this->args['max_rows_per_page'], otherwise $this->args['default_rows_per_page']
	*/
	public function get_rows_per_page()
	{
		$rows_per_page 	= (
			$_GET[$this->args['perpage_get_var']] &&
			is_numeric( $_GET[$this->args['perpage_get_var']] ) &&
			( $_GET[$this->args['perpage_get_var']] <= $this->args['max_rows_per_page'] )
		) ? $_GET[$this->args['perpage_get_var']] : $this->args['default_rows_per_page'];
		return $rows_per_page;
	}
	
	/**
	* Gets the current page
	*
	* @return int Returns $_GET['page'] if it's set and numeric, otherwise 1
	*/
	private function get_current_page()
	{
		$current_page 	= (
			$_GET[$this->args['page_get_var']] &&
			is_numeric( $_GET[$this->args['page_get_var']] )
		) ? $_GET[$this->args['page_get_var']] : 1;
		return $current_page;
	}
	
	/**
	* Gets the current query string for use in the returned links
	*
	* @return string Returns a formatted query string with page and perpage and any other params that exist in the GET string
	*/
	private function get_query_string()
	{
		$disallow		= array( $this->args['perpage_get_var'] , $this->args['page_get_var'] );
		$disallow_count	= sizeof( $disallow ) + 1;
		$i				= $disallow_count - 1;
		$query_string	= '';
		
		foreach ( $_GET as $key => $value )
		{
			$i++;
			if ( !in_array( $key , $disallow )  )
			{
				if ( $i == $disallow_count )
				{
					$query_string .= $key . '=' . urlencode( $value );
				}
				else
				{
					$query_string .= '&' . $key . '=' . urlencode( $value );
				}	
			}
		}
		if ( !empty( $query_string ) )
		{
			return $query_string . '&';
		}
		return '';
	}
	
	/**
	* Creates the list items and links for the visible page numbers
	*
	* @return string Returns html <li><a></a></li> with active class on li
	*/
	private function visible_page_number_links()
	{
		if ( $this->last_page != 1 && $this->last_page >= $this->args['visible_page_numbers'] )
		{
			$links_array = array();
			for( $i = 1 ; $this->args['total_records'] >= $i ; $i++ )
			{
				if ( $i == $this->current_page )
					$active = 'class="active"';
				else
					$active = '';
				
				$links_array[$i] = '<li '. $active .'>'. $this->render_link( 'visible' , $i ) .'</li>';
			}
			$numbers_before = ceil( $this->args['visible_page_numbers']/2 );
			$numbers_after 	= floor( $this->args['visible_page_numbers']/2 );
			
			if ( $this->current_page <= $numbers_before )
				// beginning
				$visible = array_slice( $links_array , 0 , $this->args['visible_page_numbers'] );			
			elseif ( $this->last_page - $this->current_page <= $numbers_after )
				// end
				$visible = array_slice( $links_array, $this->last_page - $this->args['visible_page_numbers'], $this->args['visible_page_numbers'] );			
			else 
				// middle
				$visible = array_slice( $links_array , $this->current_page - $numbers_before , $this->args['visible_page_numbers'] );
				
			$links = implode( '' , $visible );
			return $links;
		}
		return '';
	}
	
	/**
	* Create the html for the next link
	*
	* @return string The html for the next link
	*/
	private function next_link()
	{
		if ( $this->current_page != $this->last_page )
		{
			$next_link = $this->render_link( 'next' , $this->next_page );
			return $next_link;
		}
		return null;
	}
	
	/**
	* Create the html for the prev link
	*
	* @return string The html for the prev link
	*/
	private function prev_link()
	{
		if ( $this->current_page != 1 )
		{
			$prev_link = $this->render_link( 'prev' , $this->prev_page );
			return $prev_link;
		}
		return null;
	}
	
	/**
	* Create the html for the first page link
	*
	* @return string The html for the first page link
	*/
	private function first_link()
	{
	
		if ( $this->current_page != 1 )
		{
			$first_link = $this->render_link( 'first' , 1 );
			return $first_link;
		}
		return null;
	}
	
	/**
	* Create the html for the last page link
	*
	* @return string The html for the last page link
	*/
	private function last_link()
	{
		if ( $this->current_page != $this->last_page )
		{
			$last_link = $this->render_link( 'last' , $this->last_page );
			return $last_link;
		}
		return null;
	}
	
	private function render_link( $type , $page )
	{
		$url = '?' . $this->get_query_string() . $this->args['perpage_get_var'] .'='. $this->rows_per_page . '&'. $this->args['page_get_var'] .'=' . $page;
		if ( $type == 'visible' )
			$link = '<a class="pagination-anchor visible-page-number-'. $page .'" href="'. $url .'">'. $page .'</a>';
		else
			$link = '<li><a class="pagination-anchor '. $type .'" href="'. $url .'">'. $this->args['link_text'][$type] .'</a></li>';
		return $link;
	}
	
	/**
	* Combine all of the links to create a div and ul
	*
	* @return string The html for the div and ul that contains the First, Prev, Visible Pages, Next, and Last links
	*/	
	private function links()
	{
		if ( $this->last_page != 0 && $this->args['total_records'] > $this->rows_per_page )
		{
			$links = '';
			
			if ( $this->args['style'] )
			{
				ob_start();
				include 'pagination.css';
				$css = ob_get_clean();
				$links .= '<style>'.$css.'</style>';
			}
				
			$links .= '<div class="pagination '.$this->args['css_class'].'"><ul>';
			$links .= $this->first_link();
			$links .= $this->prev_link();
			$links .= $this->visible_page_number_links();
			$links .= $this->next_link();
			$links .= $this->last_link();
			$links .= '</ul></div>';
			return $links;
		}
		return null;
	}
}