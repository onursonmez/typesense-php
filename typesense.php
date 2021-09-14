<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

include 'vendor/autoload.php';

use Symfony\Component\HttpClient\HttplugClient;
use Typesense\Client;

class Typesense extends CI_Controller {

	function __construct()
	{
		parent::__construct();
    }

	public function index()
	{
        $this->load->view('typesense/list');
	}

	public function sync()
	{
		try {
			$client = new Client(
				[
					'api_key' => 'XXXXXXXXXXXXXXXXXXX',
					'nodes' => [
						[
							'host' => 'XXX.XXX.XXX.XXX',
							'port' => '8108',
							'protocol' => 'http',
						],
					],
					'client' => new HttplugClient(),
				]
			);
		} catch (Exception $e) {
			echo $e->getMessage();
		}		
		
		
		$client->collections['parts']->delete(); //first delete all parts

		try {
			$client->collections->create(
				[
					'name' => 'parts',
					'fields' => [
						[
							'name' => 'group_id',
							'type' => 'int32',
							'facet' => true,
						],
						[
							'name' => 'group_name',
							'type' => 'string',
						],
						[
							'name' => 'part_id',
							'type' => 'int32',
						],
						[
							'name' => 'part_name',
							'type' => 'string',
						],
						[
							'name' => 'is_active',
							'type' => 'bool',
						]

					],
					'default_sorting_field' => 'group_id',
				]
			);

			$parts = $this->db->query("get parts query")->result();

			foreach($parts as $part)
			{
				$client->collections['parts']->documents->upsert(
					[
						'group_id'       => (int)$part->group_id,
						'group_name'     => $part->group_name,
						'part_id'       => (int)$part->part_id,
						'part_name'     => $part->part_name,
						'is_active'     => $part->is_active == 't' ? true : false,
					]
				);
			}

		} catch (Exception $e) {
			echo $e->getMessage();
			// Don't error out if the collection was not found
		}
	}

	public function get()
	{
		$term = $this->input->get('term');
		try {
			$client = new Client(
				[
					'api_key' => 'XXXXXXXXXXXXXXXXXXX',
					'nodes' => [
						[
							'host' => 'XXX.XXX.XXX.XXX',
							'port' => '8108',
							'protocol' => 'http',
						],
					],
					'client' => new HttplugClient(),
				]
			);
		} catch (Exception $e) {
			echo $e->getMessage();
		}

		try {

			header('Content-Type: application/json; charset=utf-8');

			$response = $client->collections['parts']->documents->search(
				[
					'q' => $term,
					'query_by' => 'part_name',
					//'sort_by' => '_text_match:desc',
					'group_by' => 'group_id',
					'per_page' => 250,
					'group_limit' => 99,
					//'num_typos' => 0 // 0 hata, 1 hata, 2 hata
				]
			);
			echo json_encode($response);

		} catch (Exception $e) {
			echo $e->getMessage();
			// Don't error out if the collection was not found
		}
	}
}
?>
