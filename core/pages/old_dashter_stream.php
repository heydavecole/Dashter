<?php 

class dashter_stream extends dashter_base {
	
	var $page_title = 'Dashter Social Stream';
	var $menu_title = 'Stream';
	var $menu_slug = 'dashter-stream';
	
	function __construct() {
		
		add_action( 'admin_init', array( &$this, 'init' ) );
		add_action( 'admin_menu', array( &$this, 'init_submenu' ) );
		
	}
	
	function init () {
		if ($_GET['page'] == $this->menu_slug) {
			add_thickbox();
		}
	}
	
	public function display_page () {

		// delete_option('dashter_tag_blocks');
		include 'dashter_curate_tweet.php'; 	// Curation functions
		
		// Blocking system
		// Allow a user to block a tag search term, but retain the tag on the post
			
		/*
		*** DEPRECATED 8/5/2011 *** 
		*** Ajax operation ***  
			// Make an empty array so the in_array doesn't break
		if (isset($_REQUEST['tblock']) && isset($_REQUEST['post'])){
			$post_id = $_REQUEST['post'];
			$tag_id = $_REQUEST['tblock'];
			$aTagBlocks[] = $tag_id;
			update_option('dashter_tag_blocks', $aTagBlocks);
		}	
		*/
		// print_r($aTagBlocks);
		$aTagBlocks = get_option('dashter_tag_blocks');
		$aPostBlocks = get_option('dashter_post_blocks');
		if (empty($aTagBlocks)){ 
			$aTagBlocks = array(); 
		}
		if (empty($aPostBlocks)){
			$aPostBlocks = array();
		}
		$catFilter = $_REQUEST['catfilter'];
		$tagFilter = $_REQUEST['tagfilter'];
		
		// Posts
		$filterset = false;
		if (isset($catFilter)){
			$postargs = array ( 'numberposts' => 10, 'category_name' => $catFilter );
			$filterset = true;
		} 
		if (isset($tagFilter)){
			$postargs = array ( 'numberposts' => 10, 'tag' => $tagFilter );
			$filterset = true;
		} 
		if (!$filterset){
			$postargs = array ( 'numberposts' => 10 );
		}
		
		$posts = get_posts($postargs);
		
		foreach ($posts as $post){
			$aPosts[] = array (	'id'		=>	$post->ID,
								'title'		=>	$post->post_title,
								'pubdate'	=>	$post->post_date	);
		}
		// Categories
		foreach($aPosts as $postKey=>$post){
			$theCats = get_the_category( $post['id'] );
			// Assemble + add to post array as elements
			if ($theCats){
				foreach($theCats as $cat){
					$aCats[] = array(	'id'	=>	$cat->cat_ID,
										'slug' 	=>	$cat->category_nicename,
										'name'	=>	$cat->cat_name	);
				}
			}
			$aPosts[$postKey]['categories'] = $aCats;
			unset($aCats);
			unset($theCats);
		}
		// Tags
		foreach($aPosts as $postKey=>$post){
			$theTags = get_the_tags( $post['id'] );
			if ($theTags){
				foreach($theTags as $tag){
					$aTags[] = array(	'id'	=>	$tag->term_id,
										'name'	=>	$tag->name,
										'slug'	=>	$tag->slug	);
				}
			}
			$aPosts[$postKey]['tags'] = $aTags;
			unset($aTags);
			unset($theTags);
		}
		
		
	
	?>
	<style type="text/css">
		.post-window { 	float: left;
						width: 90%; 
						padding: 5px; 
						/* margin: 1%; */
		}
		.post-window p {	font-size: 8pt; 
							padding: 0 0 0 5px;
						}
		.makebig {	
			font-size: 8pt;
			font-weight: bold;
			padding: 5px;
			color: #fff;
			background-color: #21759b;
			margin: 5px 2px;
		}
	</style>
	
	<div class="wrap">
		<div class="icon32"><img src="<?php echo DASHTER_URL; ?>images/dashtericon-v1-32.png"></div>
		
		<h2 id="dashterTitle">Dashter - Stream v2</h2>
		<div id="statusresponse"></div>
		<a href="javascript:refreshAllCaches();" class="button-secondary" style="float: right;">Refresh All Caches</a>
		<span style="float: right; color: #ccc;">Blocked Tags:
		<?php 
			if (!empty($aTagBlocks)){
				foreach ($aTagBlocks as $block){
					echo $block . ", ";
				}
			}
		?>
		</span>
		<p><b>FILTER:</b>
		<?php 
			if ($filterset){
				if (isset($catFilter)){
					echo " Showing Category <b> $catFilter </b> ";
				}
				if (isset($tagFilter)){
					echo " Showing Tag <b> $tagFilter </b>";
				}
				echo " <a href='admin.php?page=dashter-stream-2'>Remove Filter (show all)</a>";
			}
			 else {
				$categories = get_categories();
				echo "Recent Posts. Filter by Category: ";
				foreach ($categories as $cat){
					echo " <a style='text-decoration: none; background-color: #ddf; white-space: nowrap; border-radius: 4px; border: solid 1px #009; padding: 2px 4px;' href='admin.php?page=dashter-stream-2&catfilter=" . $cat->category_nicename . "'>" . $cat->name . "</a> ";
				}
			}
		?>
		</p>
		<!--
		LAYOUT FIX EXPERIMENT 8-9
		<div style="width: 100%; padding: 0; margin: 0;">
			<div style="float:left; width: 49.5%; padding: 0; margin: 0;" id="col-0">
			</div>
			<div style="float: right; width: 49.5%; padding: 0; margin: 0;" id="col-1">
			</div>
			<div class="clear"></div>
		</div>
		-->
		
	<!--		<div id="dashboard-widgets-wrap">
				<div id="dashboard-widgets" class="metabox-holder">
					<div class='postbox-container' style='width:100%;'>
						<div id="normal" class="meta-box">
							<div class="postbox" >
								<span style='float: right; margin: 5px 10px 0 0;'></span>
								<h3 class='hndle'><span>Your Social Site</span></h3> -->
								<div style="padding: 10px;">
								<?php 
								$halfSet = count($aPosts); 
								?>
								<table width="100%" ><tr><td width="50%" valign="top" align="left">
								<?php
									$i=0;
									foreach ($aPosts as $post){
										$i++;
										if ( ($i == intval ( ceil ( $halfSet / 2 ) ) + 1 ) ){
											echo "</td><td width='50%' valign='top' align='left'>";
										}
										
										if ($i==11){
											break;
										}	
										$theTags = $post['tags'];
										$theCats = $post['categories'];
										if ($theTags){
											foreach ($theTags as $tag){
												if (!in_array(($tag['id']), $aTagBlocks)){
													$sQuery .= "#" . strtolower(str_replace(" ", "", $tag['name'])) . " OR ";
												}
											}
											if (!empty($sQuery)){
												$sQuery = substr($sQuery, 0, (strlen($sQuery) - 4));
											} else {
												$sQuery = 'none';
											}
										} else {
											$sQuery = 'none';
										}
										?>
										<div class="post-window postbox" id="<?php echo $post['id']; ?>" dquery="<?php echo $sQuery; ?>" style="padding: 0; margin: 0 0 20px 0;">
											<span style="float: right; color: #999; font-size: 8pt; padding: 6px 2px;">Found in &#187; 
											<?php 
											if ($theCats){
												foreach ($theCats as $cat){
													// echo "<a href='admin.php?page=dashter-stream-2&catfilter=" . $cat['id'] . "'>" . $cat['name'] . "</a> ";
													echo "<a style='text-decoration: none; background-color: #ddf; white-space: nowrap; border-radius: 4px; border: solid 1px #009; padding: 2px 4px;' href='admin.php?page=dashter-stream-2&catfilter=" . $cat['slug'] . "'>" . $cat['name'] . "</a> ";
												}
											}
											?>
											</span>
											<h3 class="hndle" style="line-height: 1em; font-size: 12pt; font-weight: bold; margin-bottom: 2px; padding: 10px;"><span><?php echo $post['title']; ?></span></h3>
											<div class="inside">
											
											</div>
										</div>
										<?php 
										unset($sQuery);
										unset($theTags);
									}
								?>		
								
								<br class="clear" />
								</td></tr></table>				
								</div>
							<!-- </div>
						</div>
					</div>
				</div>
			</div>
		</div> -->
	</div>
	<br class="clear" />
	<?php 

	}

}

new dashter_stream;
