<?php
require 'book.php';
require 'search.php';
ini_set('user_agent', 'asdBot');
define('getContext', stream_context_create(array('http' => array('proxy' => 'tcp://127.0.0.1:7890'))));
function pageSort($page, $sort)
{
	return (($page >= 1) ? '&page=' . $page : '') . (($sort != null) ? (($sort == 1 || $sort == 'popular') ? '&sort=popular' : (($sort == 2 || $sort == 'date') ? '&sort=date' : '')) : '');
}
/**
 * lolo
 */
class nhtaiAPI
{
	public $domain;
	public $apis;
	function __construct($ssl = true)
	{

		$protocol = "http" . ($ssl ? 's' : '') . '://';
		$this->domain = array(
			'main' => $protocol . 'nhentai.net',
			'images' => $protocol . 'i.nhentai.net',
			'thumbs' => $protocol . 't.nhentai.net'
		);

		$this->apis = array(
			'home' => $this->domain['main'] . '/api/galleries/all?',
			'search' => $this->domain['main'] . '/api/galleries/search?query={QUERY}',
			'searchLike' => $this->domain['main'] . '/api/gallery/{BOOK_ID}/related',
			'searchTagged' => $this->domain['main'] . '/api/galleries/tagged?tag_id={TAG_ID}',
			'bookDetails' => $this->domain['main'] . '/api/gallery/{BOOK_ID}',
			'getPage' => $this->domain['images'] . '/galleries/{MEDIA_ID}/{PAGE}.{EXT}',
			'getThumb' => $this->domain['thumbs'] . '/galleries/{MEDIA_ID}/{PAGE}t.jpg',
			'getCover' => $this->domain['thumbs'] . '/galleries/{MEDIA_ID}/cover.{EXT}'

		);
	}
	function home($page = 1, $sort = null)
	{
		if ($page >= 1 && is_integer($page)) {
			$p = $this->apis['home'] . pageSort($page, $sort);
			return $p;
		} else {
			return 'page must be a number';
		}
	}
	function search($query, $page = 1, $sort = null)
	{
		if ($query != null && is_string($query) == true) {
			if ($page != null && is_integer($page) == true) {
				return str_replace("{QUERY}", str_replace('/', '+', $query), $this->apis['search']) . pageSort($page, $sort);;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	function searchLike($book_id, $page = 1, $sort = null)
	{
		if ($book_id != null && is_integer($book_id) == true) {
			return str_replace('{BOOK_ID}', $book_id, $this->apis['searchLike']) . pageSort($page, $sort);
		} else {
			return false;
		}
	}
	function searchTagged($tag_id, $page = 1, $sort = null)
	{
		if ($tag_id != null && is_integer($tag_id) == true) {
			return str_replace('{TAG_ID}', $tag_id, $this->apis['searchTagged']) . pageSort($page, $sort);
		} else {
			return false;
		}
	}
	function bookDetails($book_id)
	{
		if ($book_id != null && is_integer($book_id) == true) {
			return str_replace('{BOOK_ID}', $book_id, $this->apis['bookDetails']);
		} else {
			return false;
		}
	}
	function getPage($media_id, $page = 1, $type = 'jpg')
	{
		if ($media_id != null && is_integer($media_id)) {
			if ($page != null && is_integer($page)) {
				if ($page >= 1) {
					return str_replace(array('{MEDIA_ID}', '{PAGE}', '{EXT}'), array($media_id, $page, $type), $this->apis['getPage']);
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	function getThumb($media_id, $page = 1)
	{
		if ($media_id != null && is_integer($media_id)) {
			if ($page != null && is_integer($page)) {
				if ($page >= 1) {
					return str_replace('{MEDIA_ID}', $media_id, $this->apis['getThumb']);
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	function getCover($media_id, $type = 'jpg')
	{
		if ($media_id != null && is_integer($media_id)) {
			return str_replace(array('{MEDIA_ID}', '{EXT}'), array($media_id, $type), $this->apis['getCover']);
		} else {
			return false;
		}
	}
	function parseBook($data)
	{
		return new nhentaiBook($data);
	}
	function parseSearch($data)
	{
		return new nhentaiSearch($data);
	}
}
