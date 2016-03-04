<?php
/**
 * This class allows one to get and parse the entries of a specified year in the Albo 
 * Pretorio of the municipality of Catania.
 *  
 * Copyright 2016 Cristiano Longo
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Cristiano Longo
 */

//the following url is url-encoded
define('ALBO_CT_URL','http://www.comune.catania.gov.it/EtnaInWeb/AlboPretorio.nsf/Web%20Ricerca?OpenForm&AutoFramed');

/**
 * Convenience class to represent single entry of the municipality of Catania Albo.
 *
 * @author Cristiano Longo
 *
 */
class AlboComuneCTEntry{
	public $repertorio;
	public $link;
	public $tipo;
	public $mittente_descrizione;

	/**
	 * Create an entry by parsing a table row.
	 */
	public function __construct($row) {
		$cells=$row->getElementsByTagName("td");
		$repertorioAnchorNodes=$cells->item(1)->getElementsByTagName("a");
		if ($repertorioAnchorNodes->length==0)
			throw new Exception("No anchor found in repertorio");
		if ($repertorioAnchorNodes->length>1)
			throw new Exception("Multiple anchor nodes found in repertorio");
		$repertorioAnchorNode=$repertorioAnchorNodes->item(0);
		$this->repertorio=$repertorioAnchorNode->textContent;
		$this->link=" http://www.comune.catania.gov.it".$repertorioAnchorNode->getAttribute("href");
		$this->tipo=html_entity_decode(utf8_decode($cells->item(3)->textContent));
		$this->mittente_descrizione=html_entity_decode(utf8_decode($cells->item(4)->textContent));
	}
}
/**
 * Get and parse the entries of a single year of the Albo Pretorio of the municipality
 * of Catania.
 *
 * @author Cristiano Longo
 *
 */
class AlboComuneCTParser implements Iterator{

	private $rows;
	private $i=1;
	
	/**
	 *  Retrieve the entries relatives to a year.
	 */
	public function __construct($year) {
		$page=$this->getPage($year);
		$this->rows=$this->getRows($page);
	}
	
	/**
	 * Retrieve the page by performing a post request.
	 * 
	 * @param int $year
	 * @return string the retrieved web page
	 */
	private function getPage($year){
		$h=curl_init(ALBO_CT_URL);
		if (!$h) throw new Exception("Unable to initialize cURL session");
		curl_setopt($h, CURLOPT_POST, TRUE);
		curl_setopt($h, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($h, CURLOPT_POSTFIELDS, array("__Click" => 0, "Anno"=>$year));
		//curl_setopt($h, CURLOPT_HTTPHEADER, array("Accept-Charset: utf-8"));
		$page=curl_exec($h);
		if( $page==FALSE)
			throw new Exception("Unable to execute POST request: "+curl_error());
		curl_close($h);
		return $page;
	}
	
	/**
	 * Extract the rows of the table containing the Albo Entries from a result page.
	 *
	 * @param string $page
	 */
	private function getRows($page){
		$d=new DOMDocument();
 		$d->loadHTML($page);
 		$tables=$d->getElementsByTagName("table");
 		if ($tables->length==0)
 			throw new Exception("No table element found");
 		if ($tables->length>1)
 			throw new Exception("Multiple table elements found");
		$rows=$tables->item(0)->getElementsByTagName("tr");
		return $rows; 			
	}
	
	//Iterator functions,  see http://php.net/manual/en/class.iterator.php
	
	public function current(){
		if ($this->rows->length<2)
			return null;
		return new AlboComuneCTEntry($this->rows->item($this->i));
	}
	
	
	public function key (){
		return $this->i;
	}
	
	public function next(){
		if ($this->i<$this->rows->length)
			++$this->i;
	}
	
	public function rewind(){
		if ($this->rows->length>1)
			$this->i=1;
	}
	
	public function valid(){
		return $this->rows->length>1 && $this->i<$this->rows->length;
	}
}
?>