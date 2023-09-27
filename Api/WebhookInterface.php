<?php
 
namespace Gojiraf\Gojiraf\Api;
 
interface WebhookInterface
{
 
    /**
     * GET for get webhooks
     * 
     * @return array|string
     */
    public function get();

    /**
     * POST for creating webhooks
     * 
     * @param string $topic
     * @param string $url
     * @return string
     */
    public function create($topic, $url);

    /** 
     * DELETE for removing webhook
     * 
     * @param int $id
     * @return string
     */
    public function delete($id);
}