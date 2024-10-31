<?php
/*
* Plugin Name: Pagaris para WooCommerce
* Plugin URI: https://desk.zoho.com/portal/pagaris/kb/articles/guia-plugin-woocommerce
* Description: NOTA: ESTE PLUGIN NO DEBE SEGUIR USÁNDOSE. POR FAVOR CONTACTA A PAGARIS PARA MÁS INFORMACIÓN.
* Author: Pagaris Fintech SAPI de CV
* Author URI: https://pagaris.com
* Version: 1.1.6
*/

require 'vendor/autoload.php';
require 'logger.php';

defined('ABSPATH') or exit;

// Make sure WooCommerce is active
$active = in_array(
  'woocommerce/woocommerce.php',
  apply_filters(
    'active_plugins',
    get_option('active_plugins')
  )
);
if (!$active) { return; }

// Register this class as a WooCommerce payment gateway
add_filter('woocommerce_payment_gateways', 'pagaris_add_gateway_class');
function pagaris_add_gateway_class($gateways) {
  $gateways[] = 'WC_Pagaris_Gateway';
  return $gateways;
}

// Define constant inside 'plugins_loaded' hook.
add_action('plugins_loaded', 'pagaris_init_gateway_class');
function pagaris_init_gateway_class() {
  class WC_Pagaris_Gateway extends WC_Payment_Gateway {
    public function __construct() {
      $this->id = 'pagaris';
      $this->icon = 'https://pagaris-static-assets.s3.us-east-2.amazonaws.com/icon_woocommerce.png';
      $this->has_fields = false;
      $this->method_title = 'Pagaris';
      $this->method_description = 'Vende a meses a clientes sin tarjeta, '
        . 'recibe de contado y sin riesgos.';

      $this->supports = ['products'];

      $this->init_form_fields();

      // Load the settings.
      $this->init_settings();
      $this->title = 'Pago a meses sin tarjeta - Pagaris';
      $this->description = 'Con Pagaris, puedes pagar tu compra a meses sin '
        . 'usar una tarjeta. Realiza una solicitud 100% digital y al instante '
        . 'podemos aprobarte para que recibas tu compra ahora y pagues después '
        . 'por ella.<br>'
        . '<a href="https://pagaris.com/buyer" target="_blank">Más información'
        . '</a>';
      $this->enabled = $this->get_option('enabled');
      $this->sandbox_mode = 'yes' === $this->get_option('sandbox_mode');
      $this->private_key = $this->sandbox_mode
        ? $this->get_option('sandbox_private_key')
        : $this->get_option('private_key');
      $this->application_id = $this->get_option('application_id');
      if ($this->get_option('special_test_case_approved') == 'yes') {
        $this->special_test_case = 'instantly_approved';
      } else {
        $this->special_test_case = 'instantly_rejected';
      }

      // Save settings
      add_action(
        'woocommerce_update_options_payment_gateways_' . $this->id,
        array($this, 'process_admin_options')
      );

      // No need to add custom js, since this is not a Direct Gateway
      // add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));

      // Redirect callback
      add_action(
        'woocommerce_api_wc_pagaris_gateway',
        [$this, 'handle_redirect']
      );

      // Webhooks
      add_action(
        'woocommerce_api_pagaris_webhooks',
        [$this, 'handle_webhooks']
      );
      add_action(
        'woocommerce_api_pagaris_sandbox_webhooks',
        [$this, 'handle_sandbox_webhooks']
      );

      // Actions
      add_action(
        'woocommerce_order_status_changed',
        [$this, 'handle_order_status_changed'],
        10,
        3 // These two last parameters are set so callback gets all 3 params.
      );
    }

    public function init_form_fields(){
      $this->form_fields = array(
        'enabled' => array(
          'title'       => 'Activar',
          'label'       => 'Habilitar Pagaris como método de pago',
          'type'        => 'checkbox',
          'description' => '',
          'default'     => 'no'
        ),
        'sandbox_mode' => array(
          'title'       => 'Modo Sandbox',
          'label'       => 'Activar modo Sandbox',
          'type'        => 'checkbox',
          'description' => 'Activa modo Sandbox (usa llaves en modo Sandbox). '
            . 'Si no está seleccionado, por defecto está en Producción. Para '
            . 'poder usar el modo Producción, tu comercio debe estar aprobado.',
          'default'     => 'yes',
          'desc_tip'    => true,
        ),
        'special_test_case_approved' => array(
          'title' => 'Aprobación automática en Sandbox',
          'label' => 'Aprobar órdenes en modo Sandbox automáticamente',
          'type'        => 'checkbox',
          'description' => 'Cuando uses modo Sandbox y esta opción esté '
            . 'seleccionada, por defecto las órdenes pasarán a estado '
            . 'aprobado. Si se quita esta opción, por defecto se pasarán a '
            . 'estado rechazado.',
          'default'     => 'yes',
          'desc_tip'    => true,
        ),
        'application_id' => array(
          'title'       => 'ID de Aplicación',
          'type'        => 'text',
        ),
        'private_key' => array(
          'title'       => 'Llave privada Producción',
          'type'        => 'password'
        ),
        'sandbox_private_key' => array(
          'title'       => 'Llave privada Sandbox',
          'type'        => 'password',
        ),
      );
    }

    // Only make this gateway available for purchases made with MXN currency
    public function is_available()
    {
      return parent::is_available() && get_woocommerce_currency() == 'MXN';
    }

    // Add custom content to 'settings' page of the plugin (webhook URLs)
    public function admin_options()
    {
      parent::admin_options();
      echo "<h2>Sincronización via webhooks</h2>";
      echo "<p>Para que los pedidos de WooCommerce se actualicen cuando haya "
      . "cambios en los estados de las Órdenes de Pagaris, edita tu aplicación "
      . "en Pagaris e introduce las siguientes URLs:</p>";

      $url = add_query_arg('wc-api', 'pagaris_webhooks', home_url('/'));
      echo "<h5>URL para Webhooks: <code>${url}</code></h5>";

      $sandbox_url = add_query_arg(
        'wc-api',
        'pagaris_sandbox_webhooks',
        home_url('/')
      );
      echo "<h5>URL para Webhooks (Sandbox): <code>{$sandbox_url}</code></h5>";

      echo "<hr>Para obtener más información de nuestro servicio y registrarte, dirígete a <a href='https://pagaris.com' target='_blank'>pagaris.com</a>. Si necesitas ayuda dirígete a nuestro <a href='https://help.pagaris.com' target='_blank'>centro de soporte</a>.";
    }

    /**
     * Creates an Order in Pagaris' API and checks response status (specially
     * for sandbox Orders, since production Orders will always be 'created').
     */
    public function process_payment($order_id) {
      global $woocommerce;
      $order = wc_get_order($order_id);
      $order->update_meta_data(
        'is_sandbox',
        $this->sandbox_mode ? 'yes' : 'no'
      );

      $redirect_url = add_query_arg(
        'wc-api',
        'WC_Pagaris_Gateway',
        home_url('/')
      );
      $args = [
        'amount' => $order->get_total(),
        'metadata' => [
          'order_id' => $order_id,
          'source' => 'woocommerce_plugin'
        ],
        'redirect_url' => $redirect_url
      ];
      if ($this->sandbox_mode) {
        $args['metadata']['special_test_case'] = $this->special_test_case;
      }
      // TODO: Add `products`, once they are interpreted correctly in Pagaris
      //       using `$order->get_items()`
      // TODO: Add `metadata` fields for the Applicant once they are interpreted

      Pagaris\Pagaris::setApplicationId($this->application_id);
      Pagaris\Pagaris::setPrivateKey($this->private_key);
      try {
        $pagaris_order = Pagaris\Order::create($args);
        $order->update_meta_data('pagaris_id', $pagaris_order->id);
        $order->save();
        $note = "Se creó la Orden en Pagaris con ID {$pagaris_order->id}";
        $order->add_order_note(__($note, 'woothemes'));

        switch ($pagaris_order->status) {
          case 'created':
          return [
            'result' => 'success',
            'redirect' => $pagaris_order->url
          ];
          case 'approved':
          $this->pagaris_order_approved($order);
          return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
          ];
          case 'confirmed':
          $this->pagaris_order_confirmed($order);
          return [
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
          ];
          case 'rejected':
          $error_message = "La orden de Pagaris fue rechazada";
          wc_add_notice(__('Error:', 'woothemes') . $error_message, 'error');
          return;
          default:
          $error_message = "Estado de Orden de Pagaris desconocido";
          wc_add_notice(__('Error:', 'woothemes') . $error_message, 'error');
          return;
        }
      }
      catch (GuzzleHttp\Exception\ClientException $e) {
        write_log($e);
        if ($e->getResponse()->getStatusCode() == 422) {
          $body = json_decode($e->getResponse()->getBody()->getContents(), true);
          if ($body && $body['error'] && $body['error']['detail']) {
            $amount_errors = $body['error']['detail']['amount'];
            $error = "Para poder usar Pagaris:\n";
            foreach ($amount_errors as $amount_error) {
              $error .= "Monto {$amount_errors[0]}\n";
            }
            wc_add_notice($error, 'error');
          }
        } else {
          wc_add_notice(
            'Hubo un problema. Por favor inténtalo nuevamente. Si este error '
            . 'sigue ocurriendo, puedes seleccionar otro método de pago.',
            'error'
          );
        }
      }
      catch (\Exception $e) {
        write_log($e);
        wc_add_notice(
          'Hubo un problema. Por favor inténtalo nuevamente. Si este error '
          . 'sigue ocurriendo, puedes seleccionar otro método de pago.',
          'error'
        );
      }
    }

    /**
    * User redirected to `GET /?wc-api=WC_Pagaris_Gateway&order_id={order_id}`
    *
    * 1. Check order_id param, then
    * 2.1 Try to GET order in Production mode, or
    * 2.2 Try go GET order in Sandbox mode, then
    * 3. Check status and update WC Order
    * 4. Redirect either to thank you page, or to cart in case it failed.
    */
    public function handle_redirect() {
      try {
        $pagaris_order_id = sanitize_text_field($_GET['order_id']);
        $uuid_pattern = '/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i';
        if (!preg_match($uuid_pattern, $pagaris_order_id)) {
          throw new \Exception('Invalid order_id param');
        }
      } catch (\Exception $e) {
        wp_die('Redirección incorrecta', 'pagaris', array('response' => 500));
        return;
      }

      Pagaris\Pagaris::setApplicationId($this->application_id);
      try {
        Pagaris\Pagaris::setPrivateKey($this->get_option('private_key'));
        $pagaris_order = Pagaris\Order::get($pagaris_order_id);
        $sandbox = false;
      } catch (\Exception $e) {
        Pagaris\Pagaris::setPrivateKey(
          $this->get_option('sandbox_private_key')
        );
        try {
          $pagaris_order = Pagaris\Order::get($pagaris_order_id);
          $sandbox = true;
        } catch (\Exception $e) {
          wp_die('Redirección incorrecta', 'pagaris', array('response' => 500));
          return;
        }
      }

      try {
        $order = wc_get_order($pagaris_order->metadata['order_id']);
        switch ($pagaris_order->status) {
          case 'created':
          return wp_redirect($this->get_return_url($order));
          case 'approved':
          $this->pagaris_order_approved($order);
          return wp_redirect($this->get_return_url($order));
          case 'confirmed':
          $this->pagaris_order_confirmed($order, $sandbox);
          return wp_redirect($this->get_return_url($order));
          case 'being_paid_out':
          $this->pagaris_order_confirmed($order, $sandbox);
          return wp_redirect($this->get_return_url($order));
          case 'paid_out':
          $this->pagaris_order_confirmed($order, $sandbox);
          return wp_redirect($this->get_return_url($order));
          default: // rejected, expired or cancelled
          $error_message = "La orden de Pagaris fue rechazada";
          $order->update_status('cancelled', __($error_message, 'woocommerce'));
          $note = "No se pudo procesar el pago a través de Pagaris. Intenta "
            ."con otro método de pago.";
          wc_add_notice(__('Error:', 'woothemes') . $note, 'error');
          return wp_redirect(wc_get_cart_url());
        }
      } catch (\Exception $e) {
        wp_die('Redirección incorrecta', 'pagaris', array('response' => 500));
        return;
      }
    }

    /**
    * `POST /?wc-api=pagaris_[sandbox_]webhooks`
    *
    * It updates the order as completed or cancelled when necessary.
    */
    public function handle_webhooks($sandbox = false)
    {
      $body = file_get_contents('php://input');
      write_log("handle_webhooks({$sandbox}) called with body {$body}");
      Pagaris\Pagaris::setApplicationId($this->application_id);
      $key_name = $sandbox ? 'sandbox_private_key' : 'private_key';
      Pagaris\Pagaris::setPrivateKey($this->get_option($key_name));

      if ($this->valid_signature($sandbox)) {
        $body = json_decode(file_get_contents('php://input'), true);
        if ($body && $body['event']['type'] == 'order.status_update') {
          $order_id = $body['payload']['order']['metadata']['order_id'];
          $order = wc_get_order($order_id);
          $pagaris_order_status = $body['payload']['order']['status'];
          $this->check_order_on_hold_from_webhook($order,
            $pagaris_order_status);
          $this->check_order_completion_from_webhook($order,
            $pagaris_order_status, $sandbox);
          $this->check_order_cancellation_from_webhook($order,
            $pagaris_order_status);
        }
      }
    }

    public function handle_sandbox_webhooks()
    {
      $this->handle_webhooks(true);
    }

    public function check_order_on_hold_from_webhook($order, $pag_status)
    {
      $order_status = $order->get_status();
      if ($pag_status == 'approved' && $order_status != 'on-hold') {
        $note = "La orden de Pagaris se aprobó, por lo que este pedido se está"
        . " poniendo En espera.\n";
        $order->add_order_note(__($note, 'woothemes'));
        $order->update_status('on-hold');
      }
    }

    public function check_order_completion_from_webhook($order, $pag_status,
      $sandbox)
    {
      $order_status = $order->get_status();
      if ($pag_status == 'confirmed'
        // Final action made in this function can set it to either one of these
        && !($order_status == 'completed' || $order_status == 'processing')
      ) {
        $note = "La orden de Pagaris se confirmó"
          . ($sandbox ? " en modo SANDBOX" : "") . " por lo que "
          . ($sandbox ? "NO" : "") . " será pagada al Comercio.\n";
        $order->add_order_note(__($note, 'woothemes'));
        $order->payment_complete();
      }
    }

    public function check_order_cancellation_from_webhook($order, $ps)
    {
      $st = $order->get_status();
      if (($ps == 'rejected' || $ps == 'expired' || $ps == 'cancelled')
        && $st != 'cancelled'
      ) {
        switch ($ps) {
          case 'rejected':
            $status_str = 'rechazada';
            break;
          case 'expired':
            $status_str = 'expirada';
            break;
          case 'cancelled':
            $status_str = 'cancelada';
            break;
        }
        $error_message = "La orden de Pagaris fue " . $status_str
          . ", por lo que este pedido se ha cancelado.\n";
        $order->update_status('cancelled', __($error_message, 'woocommerce'));
      }
    }

    public function valid_signature($sandbox)
    {
      try {
        if (isset($_SERVER["HTTP_AUTHORIZATION"])) {
          $auth_header = $_SERVER["HTTP_AUTHORIZATION"];
          $value = explode(" ", $auth_header)[1];
          list($app_id, $timestamp, $signature) = explode(":", $value);
          $body = file_get_contents('php://input');
          $path = '/';

          $valid = Pagaris\Pagaris::verifyWebhookSignature([
            'signature' => $signature,
            'timestamp' => $timestamp,
            'path' => $path,
            'body' => $body
          ]);
          return $valid;
        }
      } catch (\Exception $e) {
        return false;
      }
    }

    public function pagaris_order_approved($order)
    {
      global $woocommerce;

      $note = "La orden de Pagaris se aprobó y está lista para confirmación "
        ."del Comercio.\n";
      $order->update_status('on-hold', __($note, 'woocommerce'));
      $woocommerce->cart->empty_cart();
    }

    public function pagaris_order_confirmed($order, $sandbox = null)
    {
      global $woocommerce;

      $sandbox = $sandbox ?: $this->sandbox_mode;
      $note = "La orden de Pagaris se confirmó"
        . ($sandbox ? " en modo SANDBOX" : "")
        . " y será pagada al Comercio.\n";
      $order->add_order_note(__($note, 'woothemes'));
      $order->payment_complete();
      $woocommerce->cart->empty_cart();
    }

    /**
     * Tries to confirm or cancel a Pagaris Order. If status changed to
     * 'processing' or 'completed', we try to confirm the Pagaris Order. If
     * status changed to 'cancelled', we try to cancel the Pagaris Order.
     *
     * NOTE: This will be called even if status was changed by us, so handle
     *       API errors gracefully. In other words, we can't be sure that this
     *       will always change the Pagaris Order status. We are rethrowing
     *       catched exceptions, in case this can stop the action from taking
     *       place, or in case this error is somehow visible to the user.
     */
    public function handle_order_status_changed($order_id, $from, $to)
    {
      $order = wc_get_order($order_id);
      $pagaris_order_id = $order->get_meta('pagaris_id');
      if (!empty($pagaris_order_id)) {
        Pagaris\Pagaris::setApplicationId($this->application_id);
        $sandbox = $order->get_meta('is_sandbox') == 'yes';
        $key_name = $sandbox ? 'sandbox_private_key' : 'private_key';
        Pagaris\Pagaris::setPrivateKey($this->get_option($key_name));
        try {
          $pagaris_order = Pagaris\Order::get($pagaris_order_id);
        } catch (\Exception $e) {
          $message = "handle_order_status_changed() could not GET order "
            . " {$pagaris_order_id}";
          write_log($message);
          write_log($e);
          throw $e;
          return;
        }
      }

      if (($to == 'processing' || $to == 'completed')) {
        try {
          $pagaris_order->status == 'approved' && $pagaris_order->confirm();
        } catch (\Exception $e) {
          write_log("Could not confirm order {$pagaris_order_id}");
          write_log($e);
          throw $e;
        }
      } elseif ($to == 'cancelled') {
        try {
          if (in_array($pagaris_order->status, ['approved', 'created'])) {
            $pagaris_order->cancel();
          }
        } catch (\Exception $e) {
          write_log("Could not cancel order {$pagaris_order_id}");
          write_log($e);
          throw $e;
        }
      }
    }
  }
}
