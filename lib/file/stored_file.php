<?php  //$Id: stored_file.php,v 1.14 2008/09/21 19:53:46 skodak Exp $

/**
 * Class representing local files stored in sha1 file pool
 */
class stored_file {
    private $fs;
    private $file_record;

    /**
     * Constructor
     * @param object $fs file  storage instance
     * @param object $file_record description of file
     */
    public function __construct($fs, $file_record) {
        $this->fs = $fs;
        $this->file_record = clone($file_record);
    }

    /**
     * Is this a directory?
     * @return bool
     */
    public function is_directory() {
        return $this->file_record->filename === '.';
    }

    /**
     * Delete file
     * @return success
     */
    public function delete() {
        global $DB;
        $this->fs->mark_delete_candidate($this->file_record->contenthash);
        return $DB->delete_records('files', array('id'=>$this->file_record->id));
    }

    /**
     * Protected - developers must not gain direct access to this function
     * NOTE: do not make this public, we must not modify or delete the pool files directly! ;-)
     * @return ful path to pool file with file content
     **/
    protected function get_content_file_location() {
        $filedir = $this->fs->get_filedir();
        $contenthash = $this->file_record->contenthash;
        $l1 = $contenthash[0].$contenthash[1];
        $l2 = $contenthash[2].$contenthash[3];
        $l3 = $contenthash[4].$contenthash[5];
        return "$filedir/$l1/$l2/$l3/$contenthash";
    }

    /**
    * adds this file path to a curl request (POST only)
    *
    * @param curl $curlrequest the curl request object
    * @param string $key what key to use in the POST request
    */
    public function add_to_curl_request(&$curlrequest, $key) {
        $curlrequest->_tmp_file_post_params[$key] = '@' . $this->get_content_file_location();
    }

    /**
     * Returns file handle - read only mode, no writing allowed into pool files!
     * @return file handle
     */
    public function get_content_file_handle() {
        $path = $this->get_content_file_location();
        if (!is_readable($path)) {
            throw new file_exception('storedfilecannotread');
        }
        return fopen($path, 'rb'); //binary reading only!!
    }

    /**
     * Dumps file content to page
     * @return file handle
     */
    public function readfile() {
        $path = $this->get_content_file_location();
        if (!is_readable($path)) {
            throw new file_exception('storedfilecannotread');
        }
        readfile($path);
    }

    /**
     * Returns file content as string
     * @return string content
     */
    public function get_content() {
        $path = $this->get_content_file_location();
        if (!is_readable($path)) {
            throw new file_exception('storedfilecannotread');
        }
        return file_get_contents($this->get_content_file_location());
    }

    /**
     * Copy content of file to give npathname
     * @param string $pathnema rela path to new file
     * @return bool success
     */
    public function copy_content_to($pathname) {
        $path = $this->get_content_file_location();
        if (!is_readable($path)) {
            throw new file_exception('storedfilecannotread');
        }
        return copy($path, $pathname);
    }

    /**
     * List contents of archive
     * @param object $file_packer
     * @return array of file infos
     */
    public function list_files(file_packer $packer) {
        $archivefile = $this->get_content_file_location();
        return $packer->list_files($archivefile);
    }

    /**
     * Extract file to given file path (real OS filesystem), existing files are overwrited
     * @param object $file_packer
     * @param string $pathname target directory
     * @return mixed list of processed files; false if error
     */
    public function extract_to_pathname(file_packer $packer, $pathname) {
        $archivefile = $this->get_content_file_location();
        return $packer->extract_to_pathname($archivefile, $pathname);
    }

    /**
     * Extract file to given file path (real OS filesystem), existing files are overwrited
     * @param object $file_packer
     * @param int $contextid
     * @param string $filearea
     * @param int $itemid
     * @param string $pathbase
     * @param int $userid
     * @return mixed list of processed files; false if error
     */
    public function extract_to_storage(file_packer $packer, $contextid, $filearea, $itemid, $pathbase, $userid=null) {
        $archivefile = $this->get_content_file_location();
        return $packer->extract_to_storage($archivefile, $contextid, $filearea, $itemid, $pathbase);
    }

    /**
     * Add file/directory into archive
     * @param object $filearch
     * @param string $archivepath pathname in archive
     * @return bool success
     */
    public function archive_file(file_archive $filearch, $archivepath) {
        if ($this->is_directory()) {
            return $filearch->add_directory($archivepath);
        } else {
            $path = $this->get_content_file_location();
            if (!is_readable($path)) {
                return false;
            }
            return $filearch->add_file_from_pathname($archivepath, $path);
        }
    }

    /**
     * Returns information about image,
     * information is determined from the file content
     * @return mixed array with width, height and mimetype; false if not an image
     */
    public function get_imageinfo() {
        if (!$imageinfo = getimagesize($this->get_content_file_location())) {
            return false;
        }
        $image = array('width'=>$imageinfo[0], 'height'=>$imageinfo[1], 'mimetype'=>image_type_to_mime_type($imageinfo[2]));
        if (empty($image['width']) or empty($image['height']) or empty($image['mimetype'])) {
            // gd can not parse it, sorry
            return false;
        }
        return $image;
    }

    /**
     * Verifies the file is a valid web image - gif, png and jpeg only.
     * It should be ok to serve this image from server without any other security workarounds.
     * @return bool true if file ok
     */
    public function is_valid_image() {
        $mimetype = $this->get_mimetype();
        if ($mimetype !== 'image/gif' and $mimetype !== 'image/jpeg' and $mimetype !== 'image/png') {
            return false;
        }
        if (!$info = $this->get_imageinfo()) {
            return false;
        }
        if ($info['mimetype'] !== $mimetype) {
            return false;
        }
        // ok, GD likes this image
        return true;
    }

    /**
     * Returns parent directory, creates missing parents if needed
     * @return object stored_file
     */
    public function get_parent_directory() {
        if ($this->file_record->filepath === '/' and $this->file_record->filename === '.') {
            //root dir does not have parent
            return null;
        }

        if ($this->file_record->filename !== '.') {
            return $this->fs->create_directory($this->file_record->contextid, $this->file_record->filearea, $this->file_record->itemid, $this->file_record->filepath);
        }

        $filepath = $this->file_record->filepath;
        $filepath = trim($filepath, '/');
        $dirs = explode('/', $filepath);
        array_pop($dirs);
        $filepath = implode('/', $dirs);
        $filepath = ($filepath === '') ? '/' : "/$filepath/";

        return $this->fs->create_directory($this->file_record->contextid, $this->file_record->filearea, $this->file_record->itemid, $filepath);
    }

    public function get_contextid() {
        return $this->file_record->contextid;
    }

    public function get_filearea() {
        return $this->file_record->filearea;
    }

    public function get_itemid() {
        return $this->file_record->itemid;
    }

    public function get_filepath() {
        return $this->file_record->filepath;
    }

    public function get_filename() {
        return $this->file_record->filename;
    }

    public function get_userid() {
        return $this->file_record->userid;
    }

    public function get_filesize() {
        return $this->file_record->filesize;
    }

    public function get_mimetype() {
        return $this->file_record->mimetype;
    }

    public function get_timecreated() {
        return $this->file_record->timecreated;
    }

    public function get_timemodified() {
        return $this->file_record->timemodified;
    }

    public function get_status() {
        return $this->file_record->status;
    }

    public function get_id() {
        return $this->file_record->id;
    }

    public function get_contenthash() {
        return $this->file_record->contenthash;
    }

    public function get_pathnamehash() {
        return $this->file_record->pathnamehash;
    }
}
