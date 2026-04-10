<?php
/**
 * Mail queue handling for delayed delivery.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dienstplan_Mail_Queue {
    const QUEUE_OPTION = 'dp_mail_queue_items';
    const LOG_OPTION = 'dp_mail_queue_log';
    const LAST_RUN_OPTION = 'dp_mail_queue_last_run';
    const MODE_OPTION = 'dp_mail_delivery_mode';
    const INTERVAL_OPTION = 'dp_mail_queue_interval_minutes';
    const BATCH_OPTION = 'dp_mail_queue_batch_size';
    const APPLIED_INTERVAL_OPTION = 'dp_mail_queue_applied_interval_minutes';
    const CRON_HOOK = 'dp_process_mail_queue';
    const CRON_SCHEDULE = 'dp_mail_queue_interval';
    const MAX_LOG_ITEMS = 250;

    private static function resolve_meta_type($meta) {
        if (!is_array($meta)) {
            return '';
        }

        $type = isset($meta['type']) ? sanitize_key((string) $meta['type']) : '';
        return $type;
    }

    private static function resolve_meta_value($meta, $key) {
        if (!is_array($meta) || !isset($meta[$key])) {
            return '';
        }

        return sanitize_text_field((string) $meta[$key]);
    }

    public static function get_delivery_mode() {
        $mode = (string) get_option(self::MODE_OPTION, 'queue');
        return in_array($mode, array('queue', 'immediate'), true) ? $mode : 'queue';
    }

    public static function get_interval_minutes() {
        $interval = intval(get_option(self::INTERVAL_OPTION, 5));
        if ($interval < 1) {
            $interval = 1;
        }
        return $interval;
    }

    public static function get_batch_size() {
        $batch = intval(get_option(self::BATCH_OPTION, 20));
        if ($batch < 1) {
            $batch = 1;
        }
        if ($batch > 200) {
            $batch = 200;
        }
        return $batch;
    }

    public static function register_cron_schedule($schedules) {
        $interval_minutes = self::get_interval_minutes();
        $schedules[self::CRON_SCHEDULE] = array(
            'interval' => $interval_minutes * MINUTE_IN_SECONDS,
            'display' => sprintf(__('Dienstplan Mail Queue (%d Minuten)', 'dienstplan-verwaltung'), $interval_minutes),
        );

        return $schedules;
    }

    public static function ensure_scheduled() {
        if (self::get_delivery_mode() !== 'queue') {
            wp_clear_scheduled_hook(self::CRON_HOOK);
            return;
        }

        $interval_minutes = self::get_interval_minutes();
        $applied = intval(get_option(self::APPLIED_INTERVAL_OPTION, 0));

        if ($applied !== $interval_minutes) {
            wp_clear_scheduled_hook(self::CRON_HOOK);
            update_option(self::APPLIED_INTERVAL_OPTION, $interval_minutes, false);
        }

        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 60, self::CRON_SCHEDULE, self::CRON_HOOK);
        }
    }

    public static function enqueue_mail($to, $subject, $message, $headers = array(), $meta = array()) {
        $mail_type = self::resolve_meta_type($meta);
        $mail_source = self::resolve_meta_value($meta, 'source');
        $mail_reason = self::resolve_meta_value($meta, 'reason');
        $recipients = self::normalize_recipients($to);
        if (empty($recipients)) {
            self::append_log(array(
                'time' => current_time('mysql'),
                'status' => 'skipped',
                'to' => is_array($to) ? implode(', ', $to) : (string) $to,
                'subject' => (string) $subject,
                'error' => '',
                'attempts' => 0,
                'type' => $mail_type,
                'source' => $mail_source,
                'reason' => !empty($mail_reason) ? $mail_reason : 'invalid_recipient',
            ));
            return false;
        }

        if (self::get_delivery_mode() === 'immediate') {
            $mail_result = wp_mail($recipients, (string) $subject, (string) $message, $headers);
            $error_message = '';
            if (!$mail_result) {
                global $phpmailer;
                if (isset($phpmailer) && !empty($phpmailer->ErrorInfo)) {
                    $error_message = (string) $phpmailer->ErrorInfo;
                }
            }

            self::append_log(array(
                'time' => current_time('mysql'),
                'status' => $mail_result ? 'sent' : 'failed',
                'to' => implode(', ', $recipients),
                'subject' => (string) $subject,
                'error' => $error_message,
                'attempts' => 1,
                'type' => $mail_type,
                'source' => $mail_source,
                'reason' => $mail_result ? '' : 'wp_mail_failed',
            ));

            return (bool) $mail_result;
        }

        $queue = get_option(self::QUEUE_OPTION, array());
        if (!is_array($queue)) {
            $queue = array();
        }

        $item = array(
            'id' => uniqid('dp_mail_', true),
            'to' => $recipients,
            'subject' => (string) $subject,
            'message' => (string) $message,
            'headers' => is_array($headers) ? array_values($headers) : array((string) $headers),
            'meta' => is_array($meta) ? $meta : array(),
            'created_at' => current_time('mysql'),
            'attempts' => 0,
            'retry_at' => 0,
        );

        $queue[] = $item;
        update_option(self::QUEUE_OPTION, $queue, false);

        self::append_log(array(
            'time' => current_time('mysql'),
            'status' => 'queued',
            'to' => implode(', ', $recipients),
            'subject' => (string) $subject,
            'error' => '',
            'attempts' => 0,
            'type' => $mail_type,
            'source' => $mail_source,
            'reason' => $mail_reason,
        ));

        return true;
    }

    public static function process_queue() {
        $queue = get_option(self::QUEUE_OPTION, array());
        if (!is_array($queue) || empty($queue)) {
            update_option(self::LAST_RUN_OPTION, current_time('mysql'), false);
            return array('processed' => 0, 'sent' => 0, 'failed' => 0, 'remaining' => 0);
        }

        $batch_size = self::get_batch_size();
        $now_ts = time();
        $processed = 0;
        $sent = 0;
        $failed = 0;

        foreach ($queue as $index => $item) {
            if ($processed >= $batch_size) {
                break;
            }

            $retry_at = isset($item['retry_at']) ? intval($item['retry_at']) : 0;
            if ($retry_at > 0 && $retry_at > $now_ts) {
                continue;
            }

            $to = isset($item['to']) ? $item['to'] : array();
            $subject = isset($item['subject']) ? (string) $item['subject'] : '';
            $message = isset($item['message']) ? (string) $item['message'] : '';
            $headers = isset($item['headers']) && is_array($item['headers']) ? $item['headers'] : array();
            $mail_type = self::resolve_meta_type(isset($item['meta']) ? $item['meta'] : array());
            $mail_source = self::resolve_meta_value(isset($item['meta']) ? $item['meta'] : array(), 'source');

            $processed++;
            $mail_result = wp_mail($to, $subject, $message, $headers);

            if ($mail_result) {
                $sent++;
                self::append_log(array(
                    'time' => current_time('mysql'),
                    'status' => 'sent',
                    'to' => is_array($to) ? implode(', ', $to) : (string) $to,
                    'subject' => $subject,
                    'error' => '',
                    'attempts' => intval(isset($item['attempts']) ? $item['attempts'] : 0) + 1,
                    'type' => $mail_type,
                    'source' => $mail_source,
                    'reason' => '',
                ));
                unset($queue[$index]);
                continue;
            }

            $failed++;
            $attempts = intval(isset($item['attempts']) ? $item['attempts'] : 0) + 1;
            $error_message = '';
            global $phpmailer;
            if (isset($phpmailer) && !empty($phpmailer->ErrorInfo)) {
                $error_message = (string) $phpmailer->ErrorInfo;
            }

            if ($attempts >= 3) {
                self::append_log(array(
                    'time' => current_time('mysql'),
                    'status' => 'failed',
                    'to' => is_array($to) ? implode(', ', $to) : (string) $to,
                    'subject' => $subject,
                    'error' => $error_message,
                    'attempts' => $attempts,
                    'type' => $mail_type,
                    'source' => $mail_source,
                    'reason' => 'wp_mail_failed',
                ));
                unset($queue[$index]);
            } else {
                $item['attempts'] = $attempts;
                $item['retry_at'] = $now_ts + (5 * MINUTE_IN_SECONDS);
                $queue[$index] = $item;
                self::append_log(array(
                    'time' => current_time('mysql'),
                    'status' => 'retry',
                    'to' => is_array($to) ? implode(', ', $to) : (string) $to,
                    'subject' => $subject,
                    'error' => $error_message,
                    'attempts' => $attempts,
                    'type' => $mail_type,
                    'source' => $mail_source,
                    'reason' => 'wp_mail_failed',
                ));
            }
        }

        $queue = array_values($queue);
        update_option(self::QUEUE_OPTION, $queue, false);
        update_option(self::LAST_RUN_OPTION, current_time('mysql'), false);

        return array(
            'processed' => $processed,
            'sent' => $sent,
            'failed' => $failed,
            'remaining' => count($queue),
        );
    }

    public static function get_queue_items() {
        $queue = get_option(self::QUEUE_OPTION, array());
        return is_array($queue) ? $queue : array();
    }

    public static function get_log_items() {
        $log = get_option(self::LOG_OPTION, array());
        return is_array($log) ? $log : array();
    }

    public static function get_last_run() {
        return (string) get_option(self::LAST_RUN_OPTION, '');
    }

    public static function get_stats() {
        return array(
            'mode' => self::get_delivery_mode(),
            'queue_count' => count(self::get_queue_items()),
            'log_count' => count(self::get_log_items()),
            'last_run' => self::get_last_run(),
            'interval_minutes' => self::get_interval_minutes(),
            'batch_size' => self::get_batch_size(),
            'next_run' => wp_next_scheduled(self::CRON_HOOK),
        );
    }

    public static function clear_log() {
        delete_option(self::LOG_OPTION);
    }

    private static function append_log($entry) {
        $log = get_option(self::LOG_OPTION, array());
        if (!is_array($log)) {
            $log = array();
        }

        array_unshift($log, $entry);
        $log = array_slice($log, 0, self::MAX_LOG_ITEMS);
        update_option(self::LOG_OPTION, $log, false);
    }

    private static function normalize_recipients($to) {
        $recipients = array();

        if (is_array($to)) {
            foreach ($to as $address) {
                $email = sanitize_email((string) $address);
                if ($email !== '' && is_email($email)) {
                    $recipients[] = $email;
                }
            }
        } else {
            $parts = preg_split('/[\r\n,;]+/', (string) $to);
            foreach ((array) $parts as $part) {
                $email = sanitize_email(trim((string) $part));
                if ($email !== '' && is_email($email)) {
                    $recipients[] = $email;
                }
            }
        }

        return array_values(array_unique($recipients));
    }
}
