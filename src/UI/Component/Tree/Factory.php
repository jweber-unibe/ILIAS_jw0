<?php
declare(strict_types=1);

namespace ILIAS\UI\Component\Tree;

/**
 * Tree factory
 */
interface Factory
{
	/**
	 * ---
	 * description:
	 *   purpose: >
	 *     Nodes are entries in a Tree. They represent a level in the Tree's
	 *     data hierarchy.
	 *   composition: >
	 *     X
	 *   effect: >
	 *     X
	 *
	 * context:
	 *   - Nodes will only occur in Trees.
	 *
	 * rules:
	 *   usage:
	 *     1: Nodes MUST only be used in a Tree.
	 *     2: >
	 *       Nodes SHOULD NOT be constructed with subnodes. This is the job
	 *       of the Tree's recursion-class.
	 *
	 * ---
	 * @return \ILIAS\UI\Component\Tree\Node\Factory
	 */
	public function node(): Node\Factory;

	/**
	 * ---
	 * description:
	 *   purpose: >
	 *     An Expandable Tree focusses on the exploration of hierarchically
	 *     structured data. Its nodes can be expanded to reveal the underlying
	 *     nodes; nodes in the Expandable Tree can also be closed to hide all
	 *     underlying nodes. This lets the user decide on the simultaneously
	 *     shown levels of the data's hierarchy.
	 *   composition: >
	 *     A Tree is composed of Nodes.
	 *     Further levels (sub-Nodes) are indicated by an Expand Glyph
	 *     for the closed state of the Node and respectively by a Collapse Glyph
	 *     for the expanded state.
	 *     If there are no sub-Nodes, no Glyph will be shown at all.
	 *   effect: >
	 *     When clicking a Node, it will expand or collapse, thus showing or hiding
	 *     its sub-Nodes.
	 *
	 * rules:
	 *   usage:
	 *     1: X
	 *   accessibility:
	 *     1: X
	 *
	 * ---
	 * @param TreeRecursion $recursion
	 *
	 * @return \ILIAS\UI\Component\Tree\Expandable
	 */
	public function expandable(TreeRecursion $recursion): Expandable;

}
