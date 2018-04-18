<?php namespace Maer\Router;

class Groups
{
    /**
     * @var array
     */
    protected $groups  = [];

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * @var string
     */
    protected $prefix  = '';

    /**
     * @var string
     */
    protected $host    = '*';


    /**
     * Push new group to the stack
     *
     * @param array  $group
     */
    public function push(array $group)
    {
        $this->groups[] = [
            'before' => $this->parseFilters($group['before'] ?? []),
            'after'  => $this->parseFilters($group['after'] ?? []),
            'host'   => $group['host'] ?? null,
            'prefix' => trim($group['prefix'] ?? '', '/'),
        ];
        $this->update();
    }


    /**
     * Remove the last group from the stack
     */
    public function pop()
    {
        array_pop($this->groups);
        $this->update();
    }


    /**
     * Append the current group data to the route
     *
     * @param  string $pattern
     * @param  array  $options
     *
     * @return array
     */
    public function appendGroupInfo($pattern, array $options)
    {
        $pattern            = '/' . trim($pattern, '/');
        $options            = $this->appendFilters($options);
        $options['pattern'] = '/' . trim($this->prefix . $pattern, '/');
        $options['host']    = $options['host'] ?? $this->host;

        if (empty($options['host'])) {
            $options['host'] = '*';
        }

        return $options;
    }


    /**
     * Append filter list
     *
     * @param  array  $options
     *
     * @return array
     */
    protected function appendFilters(array $options)
    {
        foreach (['before', 'after'] as $type) {
            // Parse the filters to make them into an array
            if (empty($options[$type])) {
                $options[$type] = [];
            }

            $options[$type] = $this->parseFilters($options[$type]);

            // If we have no group filters, just return the route filters
            if (empty($this->filters[$type])) {
                continue;
            }

            $options[$type] = array_merge($this->filters[$type], $options[$type]);
        }

        return $options;
    }


    /**
     * Parse filters
     *
     * @param  array|string $filters
     *
     * @return array
     */
    protected function parseFilters($filters)
    {
        if ($filters && is_string($filters)) {
            return explode('|', $filters);
        }

        if (is_array($filters)) {
            return $filters;
        }

        return [];
    }


    /**
     * Update the current group info
     */
    protected function update()
    {
        $this->prefix  = '';
        $this->filters = [
            'before' => [],
            'after'  => [],
        ];
        $this->host    = '*';

        foreach ($this->groups as $group) {
            if ($group['prefix']) {
                $this->prefix .= '/' . $group['prefix'];
            }

            if ($group['before']) {
                $this->filters['before'] = array_merge($this->filters['before'], $group['before']);
            }

            if ($group['after']) {
                $this->filters['after'] = array_merge($this->filters['after'], $group['after']);
            }

            if ($group['host']) {
                $this->host = $group['host'];
            }
        }
    }
}
