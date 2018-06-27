<?php
    /*
    *  Copyright (c) Codiad & Olivier Auverlot, distributed
    *  as-is and without warranty under the MIT License. See
    *  [root]/license.txt for more. This information must remain intact.
    */
class ChromeZipBuilder {
    private $projectName;
	private $buildFolder = 'Build';
	public $buildPath;	
	
	function __construct($_projectName) {
		$this->projectName = $_projectName;
		$this->buildPath = WORKSPACE . '/' . $this->projectName . '/' . $this->buildFolder;
		$this->gitPath = WORKSPACE . '/' . $this->projectName . '/.git';
	}
    
    public function getZIP() {
    	$this->createChromeZIP();
    }
    
	public function createChromeZIP() {
		if (extension_loaded('zip')) {
			$rootPath = realpath(WORKSPACE . '/' . $this->projectName);

			/* Create an array of objects to exclude from our zip file */
			$excludeFilesFromZIP = array('NOTES.md', 'TODO.md');
			$excludeDirsFromZIP = array('Build', 'tmp', '.git');

	
			/* Create a temporary ZIP file */
			$zipname = tempnam('/tmp', "codiad_chrome_zip");
			$zip = new ZipArchive;
			
			
			if ($zip->open($zipname, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
				
				/* Ensure the root path is a directory */
				if (is_dir($rootPath)) {
					$iterator = new RecursiveDirectoryIterator($rootPath);
					
					/* skip dot files while iterating... */
					$iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);

					/* Build the archive content */
					$files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::LEAVES_ONLY);
	
					foreach ($files as $name => $file) {
						
						if ($file->isDir()) {
							if (!in_array($rootPath . '/' . $file, $excludeDirsFromZIP)) {
								$zip->addEmptyDir(str_replace($rootPath . '/', '', $file . '/'));
							} else {
								echo formatJSEND("notice", array("message"=>"Excluded directory from ZIP: " . $file));
							}
						} else if ($file->isFile()) {
							if (!in_array($rootPath . '/' . $file, $excludeFilesFromZIP)) {
								$zip->addFromString(str_replace($rootPath . '/', '', $file), file_get_contents($file));
							} else {
								echo formatJSEND("notice", array("message"=>"Excluded file from ZIP: " . $file));
							}
						}
						
						/* Ensure we don't add the 'Build/' directory */
//						if (!$file->isDir() && (strpos($file, $this->buildPath) || strpos($file, $this->gitPath)) === false) {
//							$filePath = $file->getRealPath();
//							$relativePath = substr($filePath, strlen($rootPath) + 1);
//							$zip->addFile($filePath, $relativePath);
//						}
					}
				}

				/* Close the ZIP file */
				$zip->close();
				
				/* Copy the temporary zip file to the build folder */
				copy($zipname, ($this->buildPath . '/' . $this->projectName . '.zip'));

				/* Cleanup the temporary zip file */
				unlink($zipname);
			} else {
				echo '{"status":"error","message":"Could not open temporary ZIP file for writing."}';
			}

		} else {
			/* The PHP 'zip' extension is not loaded */
			echo '{"status":"error","message":"The PHP zip extension is not loaded."}';
		}
    }
}
