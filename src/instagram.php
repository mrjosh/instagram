<?php

    namespace Instagram;

    /**
    * @author : Alireza Josheghani <josheghani.dev@gmail.com>
    * @package : Instagram Upload
    * @version : 1.0
    */

    class Instagram {

        /**
         * Instagram set protected varibules
         */
        protected $LoginUrl;
        protected $UploadUrl;
        protected $ConfigUrl;
        protected $loginData;
        protected $imagePATH;
        protected $agent;
        protected $UploadPath;
        protected $username;

        /**
         * Instagram constructor.
         */
        public function __construct()
        {
            /* Set login url */
            $this->LoginUrl = 'https://i.instagram.com/api/v1/accounts/login/';

            /* Set upload url */
            $this->UploadUrl = 'https://i.instagram.com/api/v1/media/upload/';

            /* Set config url */
            $this->ConfigUrl = 'https://i.instagram.com/api/v1/media/configure/';

            /* Set path of upload directory */
            $this->UploadPath = "/uploads/";

            /* Set user agent */
            $this->agent = $this->GenerateUserAgent();
        }


        /**
         * Instagram login method
         * @param array $fields
         * @return iBPage
         */
        public function login(array $fields)
        {
            $agent = $this->agent;
            $guid = $this->GenerateGuid();
            $device_id = "android-".$guid;

            /* Set datas for login */
            $data = array(
                'device_id' => $device_id,
                'guid' => $guid,
                'username' => $fields['username'],
                'password' => $fields['password'],
                'Content-Type' => "application/x-www-form-urlencoded; charset=UTF-8"
            );

            $data = json_encode($data);
            $sig = $this->signature($data);
            $data = 'signed_body='.$sig.'.'.urlencode($data).'&ig_sig_key_version=4';
            $this->loginData = $data;
            $this->username = $fields['username'];
            $browse = new iBrowse(null,$agent,false,$fields['username']);

            /* Send request to loading */
            return $browse->post($this->LoginUrl,$data);
        }


        /**
         * Instagram upload photo
         * @param $fields
         * @return array
         */
        public function uploadPhoto($fields)
        {
            $rand = rand('100000','900000');
            $this->imagePATH = $rand;
            $target_file = $this->UploadPath.$rand;
            $check = getimagesize($fields['tmp_image']);
            $imgPath = '';

            if($check !== false) {
                if (move_uploaded_file($fields['tmp_image'],$target_file.'.jpg')) {
                    $imgPath = $target_file.".jpg";
                }
            }

            /* Set data for upload */
            $data = array(
                'device_timestamp' => time(),
                'photo' => '@'.$imgPath
            );
            $agent = $this->agent;
            $upload = new iBrowse(null,$agent,true,$this->username);

            /* Send request to upload */
            $res = $upload->post($this->UploadUrl,$data);

            $json = $res->body;
            $details = json_decode($json,true);
            if($details['status'] === 'ok'){
                /* Send datas for config post */
                return $this->config($fields['caption'],$details['media_id']);
            }

            /* Delete the login cache */
            unlink('tmps/insta-'.$this->username);
            return "ERROR : Upload was failed !";
        }

        /**
         * Instagram config photo
         * @param string $caption
         * @param integer $media_id
         * @return array
         */
        public function config($caption = '', $media_id)
        {
            $agent = $this->agent;
            $caption = preg_replace("/\r|\n/", "", $caption);
            $guid = $this->GenerateGuid();
            $device_id = "android-".$guid;

            /* Set datas for config post */
            $data = array(
                'device_id' => $device_id,
                'guid' => $guid,
                'media_id' => $media_id,
                'caption' => trim($caption),
                'device_timestamp' => time(),
                'source_type' => '5',
                'filter_type' => '0',
                'extra' => '{}',
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8'
            );
            $data = json_encode($data);
            $sig = $this->signature($data);
            $jdata = 'signed_body='.$sig.'.'.urlencode($data).'&ig_sig_key_version=4';
            $config = new iBrowse(null,$agent,true,$this->username);

            /* Send request for config on server */
            $res = $config->post($this->ConfigUrl,$jdata);
            $result = $res->body;
            $arr = json_decode($result,true);

            /* Delete login cache and photo */
            unlink('tmps/insta-'.$this->username);
            unlink('uploads/'.$this->imagePATH.".jpg");
            if ($arr['status'] === 'ok'){
                /* Return uploaded datas */
                return [
                    'image_url' => $arr['media']['image_versions'][0]['url'],
                    'user' => $arr['media']['user']['username'],
                    'image_page' => 'https://www.instagram.com/p/'.$arr['media']['code']
                ];
            }

            return "Config failed !";
        }


        /**
         * generate user agent for upload
         * @return string
         */
        public function GenerateUserAgent()
        {
            $resolutions = [
                '720x1280', 
                '320x480', 
                '480x800', 
                '1024x768', 
                '1280x720', 
                '768x1024', 
                '480x320'
            ];
            $versions = [
                'GT-N7000', 
                'SM-N9000', 
                'GT-I9220', 
                'GT-I9100'
            ];
            $dpis = [
                '120', 
                '160', 
                '320', 
                '240'
            ];
            $ver = $versions[array_rand($versions)];
            $dpi = $dpis[array_rand($dpis)];
            $res = $resolutions[array_rand($resolutions)];
            return 'Instagram 4.'.mt_rand(1,2).'.'.mt_rand(0,2).' Android ('.
            mt_rand(10,11).'/'.mt_rand(1,3).'.'.mt_rand(3,5).'.'.mt_rand(0,5).'; '.
            $dpi.'; '.$res.'; samsung; '.$ver.'; '.$ver.'; smdkc210; en_US)';
        }


        /**
         * Generate G-uid for config
         * @return string
         */
        public function GenerateGuid()
        {
           return sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x', 
                mt_rand(0, 65535), 
                mt_rand(0, 65535), 
                mt_rand(0, 65535), 
                mt_rand(16384, 20479), 
                mt_rand(32768, 49151), 
                mt_rand(0, 65535), 
                mt_rand(0, 65535), 
                mt_rand(0, 65535)
            );
        }


        /**
         * Generate signature for upload
         * @param $data
         * @return string
         */
        public function signature($data) {
            return hash_hmac('sha256', $data, 'b4a23f5e39b5929e0666ac5de94c89d1618a2916');
        }

    }