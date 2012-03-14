<?php

class Pdf2jpg
{
	public $bin = null;
	public $pdf_dir = './';
	public $jpg_dir = './';
	
	public function __construct()
	{
		$this->bin = 'bin/gswin32c.exe';
	}
	
	/**
	 * pdfをjpgに変換する
	 * 
	 * @param string $pdf pdfファイル名
	 * @param string $jpg jpgファイル名
	 * @param integer $first pdfの最初の変換対象ページ
	 * @param integer $last pdfの最後の変換対象ページ
	 */
	public function convert($pdf, $jpg = null, $first = 1, $last = 1)
	{
		$this->pdf_dir = rtrim($this->pdf_dir, '/') . '/';
		$this->jpg_dir = rtrim($this->jpg_dir, '/') . '/';
		
		// jpgファイルの名前を決定
		if ($jpg === null) $jpg = preg_replace('/\.pdf$/i', '.jpg', basename($pdf));
		
		$opt = array();
		$opt[] = array('-dBATCH'); // バッチとして起動
		$opt[] = array('-dNOPAUSE'); // 標準入力を待ち受けない
		$opt[] = array('-dFirstPage', $first); // 画像生成する最初のページ
		$opt[] = array('-dLastPage', $last); // 画像生成する最後のページ
		//$opt[] = array('-g50%'); // 出力画像サイズ
		$opt[] = array('-sDEVICE', 'jpeg'); // 出力フォーマット
		$opt[] = array('-sOutputFile', $this->jpg_dir . $jpg); // 出力先ファイル
		$opt[] = array($this->pdf_dir . $pdf); // 変換対象ファイル
		
		foreach ($opt as &$o) $o = join('=', $o);
		$cmd = ' "' . $this->bin . '" ' . join(' ', $opt);
		exec($cmd);
	}
}