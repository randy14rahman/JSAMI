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

namespace Pomegranate\framework\Resource;

use \Pomegranate\framework\RpcServer as RpcServer;

class Response extends \Pomegranate\framework\Json\Response
{
    protected $omitData = false;

    public function sendHeaders()
    {
        if (headers_sent()) {
            return;
        }

        if ($this->isError()) {
            $errors = $this->getError();
            switch ($errors[0]->getCode()) {
                case RpcServer\Error::ERROR_FILE_NOT_FOUND:
                    header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
                    break;

                case RpcServer\Error::ERROR_PERMISSION_DENIED:
                    header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
                    break;

                default:
                    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                    break;
            }
            $logger = \Zend_Registry::get('logger');
            $logger->emerg(print_r($errors, true));
        }

        if (!$this->isError()
        && ($this->result instanceof \Pomegranate\framework\Service\Result\EvaluateFile
        || $this->result instanceof \Pomegranate\framework\Service\Result\DynamicResource
        || $this->result instanceof \Pomegranate\framework\Service\Result\PackJsClassesFile
        || $this->result instanceof \Pomegranate\framework\Service\Result\LocalFile)) {
            $data = $this->result->getData();
            header_remove();
            if ($this->result != null) {
                foreach ($this->result->getHeaders() as $h) {
                    header($h);
                }
            }
            if ($this->result instanceof \Pomegranate\framework\Service\Result\EvaluateFile
            || $this->result instanceof \Pomegranate\framework\Service\Result\DynamicResource
            || $this->result instanceof \Pomegranate\framework\Service\Result\PackJsClassesFile) {
                if (isset($data['etag']) && !empty($data['etag'])) {
                    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && '"'.$data['etag'].'"' == $_SERVER['HTTP_IF_NONE_MATCH']) {
                        $this->omitData = true;
                        header('HTTP/1.0 304 Not modified');
                        return;
                    }
                    header("ETag: \"{$data['etag']}\"");
                }
                if (isset($data['filename']) && !empty($data['filename'])) {
                    $disposition = isset($data['disposition']) ? $data['disposition'] : 'inline';
                    header("Content-Disposition: $disposition; filename=\"{$data['filename']}\"");
                }
                else if (isset($data['disposition'])) {
                    header("Content-Disposition: {$data['disposition']}");
                }
                header('Content-Length: ' . strlen($data['content']));
                if (isset($data['last_modified']) && !empty($data['last_modified'])) {
                    header("Last-Modified: {$data['last_modified']}");
                }
                if (isset($data['mime']) && !empty($data['mime'])) {
                    header("Content-Type: {$data['mime']}", true);
                }
                else if (isset($data['extension']) && !empty($data['extension'])) {
                    $mime = $this->get_mime_type($data['extension']);
                    header("Content-Type: {$mime}", true);
                }
                else {
                    header("Content-Type: application/x-unknown-content-type", true);
                }
            }
            else if ($this->result instanceof \Pomegranate\framework\Service\Result\LocalFile) {
                $filename = pathinfo($data['file_path'], PATHINFO_FILENAME);
                $extension = pathinfo($data['file_path'], PATHINFO_EXTENSION);
                $mime = $this->get_mime_type($extension);
                $etag = filemtime($data['file_path']);
                $lastModified = date('D, j M Y H:i:s e', $etag);

                if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && '"'.$etag.'"' == $_SERVER['HTTP_IF_NONE_MATCH']) {
                    $this->omitData = true;
                    header('HTTP/1.0 304 Not modified');
                    return;
                }

                header("ETag: \"{$etag}\"");
                header("Content-Disposition: inline; filename=\"{$filename}\"");
                header('Content-Length: ' . filesize($data['file_path']));
                header("Last-Modified: {$lastModified}");
                if ($mime === false) {
                    header("Content-Type: application/x-unknown-content-type", true);
                }
                else {
                    header("Content-Type: {$mime}", true);
                }
            }
        }
        else if (!$this->isError()) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 204 No Content');
            return;
        }
    }

    public function sendContent()
    {
        if (!$this->isError() && !$this->omitData
        && ($this->result instanceof \Pomegranate\framework\Service\Result\EvaluateFile
        || $this->result instanceof \Pomegranate\framework\Service\Result\DynamicResource
        || $this->result instanceof \Pomegranate\framework\Service\Result\PackJsClassesFile
        || $this->result instanceof \Pomegranate\framework\Service\Result\LocalFile)) {
            $data = $this->result->getData();
            if ($this->result instanceof \Pomegranate\framework\Service\Result\EvaluateFile
            || $this->result instanceof \Pomegranate\framework\Service\Result\DynamicResource
            || $this->result instanceof \Pomegranate\framework\Service\Result\PackJsClassesFile) {
                echo $data['content'];
            }
            else if ($this->result instanceof \Pomegranate\framework\Service\Result\LocalFile) {
                readfile($data['file_path']);
            }
        }
    }
}
