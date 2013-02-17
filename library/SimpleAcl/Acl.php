<?php
namespace SimpleAcl;

use SimpleAcl\Role;
use SimpleAcl\Resource;
use SimpleAcl\Rule;

use SimpleAcl\Role\RoleAggregateInterface;
use SimpleAcl\Resource\ResourceAggregateInterface;
use SimpleAcl\Exception\InvalidArgumentException;

/**
 * Access Control List (ACL) management.
 *
 */
class Acl
{
    /**
     * Contains registered rules.
     *
     * @var Rule[]
     */
    protected $rules = array();

    /**
     * Class name used when rule created from string.
     *
     * @var string
     */
    protected $ruleClass = 'SimpleAcl\Rule';

    /**
     * Set rule class.
     *
     * @param string $ruleClass
     */
    public function setRuleClass($ruleClass)
    {
        $this->ruleClass = $ruleClass;
    }

    /**
     * Return rule class.
     *
     * @return string
     */
    public function getRuleClass()
    {
        return $this->ruleClass;
    }

    /**
     * Return true if rule was already added.
     *
     * @param Rule $needRule
     * @return bool
     */
    public function hasRule(Rule $needRule)
    {
        foreach ( $this->rules as $rule ) {
            if ( $rule === $needRule ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adds rule.
     *
     * Assign $role, $resource and $action to added rule.
     * If rule was already registered only change $role, $resource and $action for that rule.
     *
     * @param Role $role
     * @param Resource $resource
     * @param Rule|string $rule
     * @param mixed $action
     * @throws InvalidArgumentException
     */
    public function addRule(Role $role, Resource $resource, $rule, $action)
    {
        if ( is_string($rule) ) {
            $ruleClass = $this->getRuleClass();
            $ruleName = $rule;
            $rule = new $ruleClass();
            /** @var Rule $rule */
            $rule->setName($ruleName);
        }

        if ( ! $rule instanceof Rule ) {
            throw new InvalidArgumentException('Rule must be an instance of SimpleAcl\Rule or string');
        }

        $rule->setRole($role);
        $rule->setResource($resource);
        $rule->setAction($action);

        if ( ! $this->hasRule($rule) ) {
            $this->unShiftRule($rule);
        }
    }

    /**
     * Add rule at the top of rules array.
     *
     * @param $rule
     */
    protected function unShiftRule($rule)
    {
        array_unshift($this->rules, $rule);
    }

    /**
     * Get names.
     *
     * @param string|RoleAggregateInterface|ResourceAggregateInterface $object
     * @return array
     */
    protected function getNames($object)
    {
        if ( is_string($object) ) {
            return array($object);
        } elseif ( $object instanceof RoleAggregateInterface ) {
            return $object->getRolesNames();
        } elseif ( $object instanceof ResourceAggregateInterface ) {
            return $object->getResourcesNames();
        }

        return array();
    }

    /**
     * Check is access allowed by some rule.
     * Returns null if rule don't match any role or resource.
     *
     * @param string|RoleAggregateInterface $roleName
     * @param string|ResourceAggregateInterface $resourceName
     * @param Rule $rule
     * @return bool|null
     */
    protected function isRuleAllow($roleName, $resourceName, Rule $rule)
    {
        $roles = $this->getNames($roleName);
        $resources = $this->getNames($resourceName);

        foreach ( $roles as $role ) {
            foreach ( $resources as $resource ) {
                $isAllowed = $rule->isAllowed($role, $resource);
                if ( $isAllowed !== null ) {
                    return $isAllowed;
                }
            }
        }

        return null;
    }

    /**
     * Checks is access allowed.
     *
     * @param string|RoleAggregateInterface $roleName
     * @param string|ResourceAggregateInterface $resourceName
     * @param string $ruleName
     * @return bool
     */
    public function isAllowed($roleName, $resourceName, $ruleName)
    {
        foreach ( $this->rules as $rule ) {
            if ( $rule->getName() == $ruleName ) {
                $isAllowed = $this->isRuleAllow($roleName, $resourceName, $rule);
                if ( $isAllowed !== null ) {
                    return $isAllowed;
                }
            }
        }

        return false;
    }

    /**
     * Remove all rules.
     *
     */
    public function removeAllRules()
    {
        $this->rules = array();
    }

    /**
     * Remove rules by rule name and (or) role and resource.
     *
     * @param null|string $roleName
     * @param null|string $resourceName
     * @param null|string $ruleName
     * @param bool $all
     */
    public function removeRule($roleName = null, $resourceName = null, $ruleName = null, $all = true)
    {
        if ( is_null($roleName) && is_null($resourceName) && is_null($ruleName) ) {
            $this->removeAllRules();
            return;
        }

        foreach ( $this->rules as $ruleIndex => $rule ) {
            if ( $ruleName === null || ($ruleName !== null && $ruleName == $rule->getName()) ) {
                if ( $roleName === null || ($roleName !== null && $rule->getRole() && $rule->getRole()->getName() == $roleName) ) {
                    if ( $resourceName === null || ($resourceName !== null && $rule->getResource() && $rule->getResource()->getName() == $resourceName) ) {
                        unset($this->rules[$ruleIndex]);
                        if ( ! $all ) {
                            return;
                        }
                    }
                }
            }
        }

    }
}
