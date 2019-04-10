<?php
/**
 * Created by PhpStorm.
 * User: Adnan Haider
 * Date: 08-Apr-19
 * Time: 17:12
 */

class ParseInputStream
{
	/**
	 * @abstract Raw input stream
	 */
	protected $input;

	public function __construct()
	{
		$input = '';
		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$input[] = (object)$_POST;
		}
		else {
			$this->input = file_get_contents('php://input');
			$boundary = $this->boundary();
			if (!strlen($boundary)) {
				$data = [
					'parameters' => $this->parse()
				];
			}
			else {
				$blocks = $this->split($boundary);
				$data = $this->blocks($blocks);
			}
			$input[] = (object)$data;
		}
		return $input;
	}

	/**
	 * @function boundary
	 * @returns string
	 */
	private function boundary()
	{
		if (!isset($_SERVER['CONTENT_TYPE'])) {
			return null;
		}
		preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'], $matches);
		return $matches[1];
	}

	/**
	 * @function parse
	 * @returns array
	 */
	private function parse()
	{
		parse_str(urldecode($this->input), $result);
		return $result;
	}

	/**
	 * @param $boundary
	 * @return array[]|false|string[]
	 */
	private function split($boundary)
	{
		$result = preg_split("/-+$boundary/", $this->input);
		array_pop($result);
		return $result;
	}

	/**
	 * @param $array
	 * @return array
	 */
	private function blocks($array)
	{
		$results = [];
		foreach ($array as $key => $value) {
			if (empty($value))
				continue;
			$block = $this->parameter($value);
			$results = array_merge($results, $block);
		}
		return $results;
	}

	/**
	 * @param $string
	 * @return array
	 */
	private function parameter($string)
	{
		$data = [];
		if (preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $string, $match)) {
			if (preg_match('/^(.*)\[\]$/i', $match[1], $tmp)) {
				$data[$tmp[1]][] = ($match[2] !== NULL ? $match[2] : '');
			}
			else {
				$data[$match[1]] = ($match[2] !== NULL ? $match[2] : '');
			}
		}
		return $data;
	}
}
