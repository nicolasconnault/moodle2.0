<?php // $Id: testportfolioplugindownload.php,v 1.1 2008/08/29 08:01:55 nicolasconnault Exp $
require_once($CFG->libdir.'/simpletest/testportfoliolib.php');
require_once($CFG->dirroot.'/portfolio/type/download/lib.php');

Mock::generate('boxclient', 'mock_boxclient');
Mock::generatePartial('portfolio_plugin_download', 'mock_downloadplugin', array('ensure_ticket', 'ensure_account_tree'));


class testPortfolioPluginDownload extends portfoliolib_test {
    public function setUp() {
        parent::setUp();
        $this->plugin = &new mock_boxnetplugin($this);
        $this->plugin->boxclient = new mock_boxclient();
    }

    public function tearDown() {
        parent::tearDown();
    }

}
?>
