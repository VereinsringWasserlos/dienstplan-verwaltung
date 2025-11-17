<?php
/**
 * Hook-Loader für das Plugin
 *
 * Registriert alle Actions und Filter für das Plugin.
 *
 * @package    Dienstplan_Verwaltung
 * @subpackage Dienstplan_Verwaltung/includes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hook-Loader Klasse
 *
 * Verwaltet alle Actions und Filter des Plugins.
 */
class Dienstplan_Loader {

    /**
     * Array von Actions
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $actions    Actions die registriert werden sollen
     */
    protected $actions;

    /**
     * Array von Filters
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $filters    Filter die registriert werden sollen
     */
    protected $filters;

    /**
     * Initialisierung
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Action hinzufügen
     *
     * @since    1.0.0
     * @param    string    $hook          WordPress Hook name
     * @param    object    $component     Referenz zur Klassen-Instanz
     * @param    string    $callback      Methoden-Name
     * @param    int       $priority      Hook-Priorität
     * @param    int       $accepted_args Anzahl akzeptierter Argumente
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Filter hinzufügen
     *
     * @since    1.0.0
     * @param    string    $hook          WordPress Hook name
     * @param    object    $component     Referenz zur Klassen-Instanz
     * @param    string    $callback      Methoden-Name
     * @param    int       $priority      Hook-Priorität
     * @param    int       $accepted_args Anzahl akzeptierter Argumente
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Hook zum Array hinzufügen
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $hooks         Hook-Array
     * @param    string    $hook          Hook-Name
     * @param    object    $component     Klassen-Instanz
     * @param    string    $callback      Callback-Methode
     * @param    int       $priority      Priorität
     * @param    int       $accepted_args Argumente
     * @return   array     Aktualisiertes Hook-Array
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    /**
     * Alle Hooks bei WordPress registrieren
     *
     * @since    1.0.0
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}
