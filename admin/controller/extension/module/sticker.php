<?php

class ControllerExtensionModuleSticker extends Controller {

    private $error = array();
    public function index() {
        $this->language->load('extension/module/sticker');

        $this->load->model('setting/setting');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('module_sticker', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');
            //DELETE CACHE IMAGE
            $this->delTree(DIR_IMAGE.'cache/');
            mkdir(DIR_IMAGE.'cache/');
            //------------------

            $this->response->redirect($this->url->link('extension/module/sticker', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));


        }
        if (isset($this->error['warning'])) {
    			$data['error_warning'] = $this->error['warning'];
    		} else {
    			$data['error_warning'] = '';
    		}
        //--
    		if (isset($this->error['code'])) {
    			$data['error_code'] = $this->error['code'];
    		} else {
    			$data['error_code'] = '';
    		}

    		$data['breadcrumbs'] = array();

    		$data['breadcrumbs'][] = array(
    			'text' => $this->language->get('text_home'),
    			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
    		);

    		$data['breadcrumbs'][] = array(
    			'text' => $this->language->get('text_extension'),
    			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
    		);

    		$data['breadcrumbs'][] = array(
    			'text' => $this->language->get('heading_title'),
    			'href' => $this->url->link('extension/module/sticker', 'user_token=' . $this->session->data['user_token'], true)
    		);

    		$data['action'] = $this->url->link('extension/module/sticker', 'user_token=' . $this->session->data['user_token'], true);

    		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

        if (isset($this->request->post['module_sticker_status'])) {
            $data['module_sticker_status'] = $this->request->post['module_sticker_status'];
        } else {
            $data['module_sticker_status'] = $this->config->get('module_sticker_status');
        }

        if (isset($this->request->post['module_sticker_productpros'])) {
            $data['module_sticker_productpros'] = $this->request->post['module_sticker_productpros'];
        } elseif ($this->config->get('module_sticker_productpros')) {
            $data['module_sticker_productpros'] = $this->config->get('module_sticker_productpros');
        } else
            $data['module_sticker_productpros'] = '30';


        if (isset($this->request->post['module_sticker_productexpros'])) {
            $data['module_sticker_productexpros'] = $this->request->post['module_sticker_productexpros'];
            //print_r('post');
        } elseif ($this->config->get('module_sticker_productexpros')) {
            //print_r('get');
            $data['module_sticker_productexpros'] = $this->config->get('module_sticker_productexpros');
        } else
            $data['module_sticker_productexpros'] = '35';

        if (isset($this->request->post['module_sticker_cache'])) {
            $data['module_sticker_cache'] = $this->request->post['module_sticker_cache'];
        } else {
            $data['module_sticker_cache'] = $this->config->get('module_sticker_cache');
        }


        $data['header'] = $this->load->controller('common/header');
    		$data['column_left'] = $this->load->controller('common/column_left');
    		$data['footer'] = $this->load->controller('common/footer');
    		$this->response->setOutput($this->load->view('extension/module/sticker', $data));
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'extension/module/sticker')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }


        if (!$this->error) {
            return true;
        } else {
            return false;
        }
    }
    //OpencartModules
    private function delTree ($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
             (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }



}

?>
