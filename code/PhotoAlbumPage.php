<?php
/**
 * This page is used to display a single album of photos.
 *
 */
class PhotoAlbumPage extends Page {
	static $db = array(
		'ThumbnailSize' => 'Int',
		'NormalSize' => 'Int',
		'MediaPerPage' => 'Int',
	);
	static $defaults = array(
		'ThumbnailSize' => 85,
		'NormalSize' => 500,
		'MediaPerPage' => 10,
	);
	static $has_one = array(
		'CoverImage' => 'PhotoAlbumPage_Photo',
	);
	static $has_many = array("Photos" => "PhotoAlbumPage_Photo",);
	static $default_parent = 'PhotoAlbumHolder';

	function getCMSFields() {
		$photoManager = new PhotoAlbumPage_Manager($this, "Photos", "PhotoAlbumPage_Photo", "Photo", array("Caption"=>"Caption"), "getCMSFields_forPopup");
		$photoManager->setUploadFolder(str_replace('assets/','',$this->AssociatedFolder()->Filename));
		$fields = parent::GetCMSFields();
		$fields->addFieldToTab("Root.Content.Configuration", new NumericField('ThumbnailSize','Thumbnail size (pixels)'));
		$fields->addFieldToTab("Root.Content.Configuration", new NumericField('NormalSize','Normal size (pixels)'));
		$fields->addFieldToTab("Root.Content.Configuration", new NumericField('MediaPerPage','Number of images per page'));
		$fields->addFieldToTab('Root.Content.AlbumPhotos', $photoManager);
		$photos = DataObject::get("PhotoAlbumPage_Photo", "PhotoAlbumPageID = ".$this->ID);
		if ( $photos && $photos->Count() > 0 ) {
			$coverPhotoField = new DropdownField('CoverImageID','Cover Photo', $photos->toDropdownMap('ID','Caption'));
			$coverPhotoField->setRightTitle('Choose a photo that will be used in holder pages.');
			$fields->addFieldToTab('Root.Content.Main', $coverPhotoField, 'Content');
		}
		return $fields;
	}

	public function ImageCount()
	{
		$images = DataObject::get("PhotoAlbumPage_Photo","PhotoAlbumPageID = {$this->ID}");
		return $images ? $images->Count() : 0;
	}
}
Object::add_extension('PhotoAlbumPage', 'AssociatedFolderDecorator');

class PhotoAlbumPage_Controller extends Page_Controller {	

	public function init() {
		parent::init();
		
		Requirements::CSS("photo_album/css/photoalbum.css");
		Requirements::javascript("http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js");
		Requirements::javascript("photo_album/javascript/jquery.photo.gallery.js");
		Requirements::javascript("photo_album/javascript/jquery.slide.show.js");


	}

	public function rotateimage()
	{
		if($image = DataObject::get_by_id("PhotoAlbumPage_Image", $this->urlParams['ID'])) {
			$rotatedImage = $this->urlParams['OtherID'] == 'cw' ? $image->RotateClockwise() : $image->RotateCounterClockwise();
			if(copy(Director::baseFolder().'/'.$rotatedImage->Filename, Director::baseFolder().'/'.$image->Filename)) {
				$image->flushCache();
				$image->clearResampledImages();
			}
			echo $image->SetHeight(200)->URL . "?t=".time();
		}
	}
}

/**
 * A wrapper that holds an image in the "PhotoAlbumPage->Photos()" collection.
 *
 */
class PhotoAlbumPage_Photo extends DataObject {
	static $db = array (
		'Caption' => 'Text',
	);

	static $has_one = array (
		'PhotoAlbumPage' => 'PhotoAlbumPage',
		'Photo' => 'PhotoAlbumPage_Image'
	);

	public function getCMSFields_forPopup()
	{
		$fields = new FieldSet();
		$fields->push(new TextareaField('Caption'));
		$fields->push(new ImageField('Photo'));

		return $fields;
	}
	
	public function onBeforeDelete() {
		parent::onBeforeDelete();
		$this->Photo()->delete();
	}

	public function Thumbnail()
	{
		$thumbnailSize = $this->PhotoAlbumPage()->ThumbnailSize;
		return $this->Photo()->CroppedImage($thumbnailSize,$thumbnailSize);
	}
	
	public function Large()
	{
		$photo = $this->Photo();
		$normalSize = $this->PhotoAlbumPage()->NormalSize;
		if($photo->Landscape()) {
			return $photo->SetWidth($normalSize);
		} else {
			return $photo->SetHeight($normalSize);
		}
	}

}

class PhotoAlbumPage_Image extends Image {
	public function generateRotateClockwise(GD $gd) 
	{
		return $gd->rotate(90);
	}
	
	public function generateRotateCounterClockwise(GD $gd)
	{
		return $gd->rotate(270);
	}
	
	public function clearResampledImages()
	{
		$files = glob(Director::baseFolder().'/'.$this->Parent()->Filename."_resampled/*-$this->Name");
	 	foreach($files as $file) {unlink($file);}
	}
	
	public function Landscape()
	{
		return $this->getWidth() > $this->getHeight();
	}
	
	public function Portrait()
	{
		return $this->getWidth() < $this->getHeight();
	}
}

class PhotoAlbumPage_Manager extends ImageDataObjectManager {
	public $popupClass = "PhotoAlbumPage_Popup";

	public function __construct($controller, $name, $sourceClass, $fileFieldName, $fieldList = null, $detailFormFields = null, $sourceFilter = "", $sourceSort = "", $sourceJoin = "") 
	{
		parent::__construct($controller, $name, $sourceClass, $fileFieldName, $fieldList, $detailFormFields, $sourceFilter, $sourceSort, $sourceJoin); 
		if(!class_exists("ImageDataObjectManager")) {
			die("<strong>Error</strong>: PhotoAlbumPage requires the DataObjectManager module.");
		}

		$this->setGridLabelField('Caption');
		$this->setAddTitle('Photos');
		// We have to specify this here or when you subclass the PhotoAlbumPage the manager can't work out the parent relationship.
		// I.E. it looks for PhotoAlbumPage_Photo -- has_one -- SubClass, rather than the correct relation of PhotoAlbumPage_Photo -- has_one -- PhotoAlbumPage. 
		$this->setParentClass('PhotoAlbumPage');
	}
	
	public function getPreviewFieldFor($fileObject, $size = 150)
	{
		if($fileObject instanceof Image) {
			$URL = $fileObject->SetHeight($size)->URL;
			return new LiteralField("icon",
				"<div class='current-image'>
					<div id='preview-image'>
						<img src='$URL' alt='' class='preview' />
						<div class='ajax-loader'><img src='dataobject_manager/images/ajax-loader.gif' /> Rotating...</div>
					</div>
					<div class='rotate-controls'>
						<a href='".$this->CounterClockwiseLink($fileObject)."' title='Rotate clockwise'><img src='photo_album/images/clockwise.gif' /></a> | 
						<a href='".$this->ClockwiseLink($fileObject)."' title='Rotate counter-clockwise'><img src='photo_album/images/counterclockwise.gif' /></a>
					</div>
					<h3>$fileObject->Filename</h3>
				</div>"
			);
		}
	}
	
	public function RotateLink($imgObj, $dir)
	{
		return "PhotoAlbumPage_Controller/rotateimage/{$imgObj->ID}/{$dir}?flush=1";
	}
	
	private function CounterClockwiseLink($fileObject)
	{
		return $this->RotateLink($fileObject, "ccw");
	}
	
	private function ClockwiseLink($fileObject)
	{
		return $this->RotateLink($fileObject, "cw");
	}
}

class PhotoAlbumPage_Popup extends FileDataObjectManager_Popup
{
	function __construct($controller, $name, $fields, $validator, $readonly, $dataObject) {
		parent::__construct($controller, $name, $fields, $validator, $readonly, $dataObject);
		Requirements::customScript("
			$(function() {
				$('.rotate-controls a').click(function() {
					link = $(this).attr('href');
					 $.ajax({
					   url: link,
					   success: function(html){
					   	$('#preview-image img.preview').attr('src', html);
					   }
					
					});
					return false;
				});
			});
			
			$().ajaxSend(function(r,s){  
			 $('.ajax-loader').slideDown();  
			});  
			   
			$().ajaxStop(function(r,s){  
			  $('.ajax-loader').slideUp();  
			});
		");
	}
}
