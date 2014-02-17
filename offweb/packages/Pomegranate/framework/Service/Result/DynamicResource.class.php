<?php
/**
 *   Copyright 2012 Mehran Ziadloo & Siamak Sobhany
 *   Pomegranate Framework Project
 *   (http://www.sourceforge.net/p/pome-framework)
 *
 *   Licensed under the Apache License, Version 2.0 (the "License");
 *   you may not use this file except in compliance with the License.
 *   You may obtain a copy of the License at
 *
 *       http://www.apache.org/licenses/LICENSE-2.0
 *
 *   Unless required by applicable law or agreed to in writing, software
 *   distributed under the License is distributed on an "AS IS" BASIS,
 *   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *   See the License for the specific language governing permissions and
 *   limitations under the License.
 **/

namespace Pomegranate\framework\Service\Result;

class DynamicResource extends \Pomegranate\framework\Service\Result
{
    protected $filename;
    protected $content;
    protected $mime;
    protected $disposition;

    public function __construct($filename, $content, $mime = false, $disposition = 'attachment')
    {
        $this->filename = $filename;
        $this->content = $content;
        $this->mime = $mime;
        $this->disposition = $disposition;
    }

    public function getData()
    {
        $etag = time();
        $lastModified = date('D, j M Y H:i:s e', $etag);

        $r = array(
            'filename' => $this->filename
            , 'disposition' => $this->disposition
            , 'etag' => $etag
            , 'last_modified' => $lastModified
            , 'content' => $this->content
        );

        if ($this->mime !== false) {
            $r['mime'] = $this->mime;
        }
        else {
            $r['extension'] = pathinfo($this->filename, PATHINFO_EXTENSION);
        }

        return $r;
    }
}
