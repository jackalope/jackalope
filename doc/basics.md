Basics
======

Node
____

### addNode($relPath, $primaryNodeTypeName = NULL, $identifier = NULL)

Creates a New node on a given Path and returns it afterwards. The Change will be written to JCR when session->save() is called.

<table>
    <tbody>
        <tr>
            <td>$relPath</td>
            <td>relative Path (from the current Node Obj) to the node you want to create <br />EXAMPLE HERE</td>
        </tr>
        <tr>
            <td>$primaryNodeTypeName</td>
            <td>defines the jcr:primaryType</td>
        </tr>
        <tr>
            <td>$identifier</td>
            <td>defines the jcr:uuid</td>
        </tr>
        <tr>
            <td>return</td>
            <td>Returns the newly added Node</td>
        </tr>
    </tbody>
</table>

### orderBefore($srcChildRelPath, $destChildRelPath)

Moves the $srcChildRelPath childnode before the $destChildRelPath chilnode. If $destChildRelPath is null the childnode will be moved at the end of the Node. Both Path’s need to be relative.

<table>
    <tbody>
        <tr>
            <td>$srcChildRelPath</td>
            <td>relative Path to where the childnode is now</td>
        </tr>
        <tr>
            <td>$destChildRelPath</td>
            <td>relative Path to the childnode of which the sourcenode will be moved in front off</td>
        </tr>
        <tr>
            <td>return</td>
            <td>NOTHING!</td>
        </tr>
    </tbody>
</table>

### setProperty($name, $value, $type = NULL)

Is used to set properties on nodes. Existing Properties will be overwritten, new ones will be created. Passing a null as $value will delete the property. The Change will be written to JCR when session->save() is called.

<table>
    <tbody>
        <tr>
            <td>$name</td>
            <td>The name of the property you want to set</td>
        </tr>
        <tr>
            <td>$value</td>
            <td>Value which the property shall have</td>
        </tr>
        <tr>
            <td>$type</td>
            <td>The type is chosen automaticly by default but you can force a certain type<br />
                Examples of Types:<br />
                \PHPCR\PropertyType::LONG => Int<br />
                \PHPCR\PropertyType::STRING => String<br />
                \PHPCR\PropertyType::BOOLEAN => bool<br />
                \PHPCR\PropertyType::DATE => date<br />
                see PHPCR/PropertyType.php for more</td>
        </tr>
        <tr>
            <td>return</td>
            <td>Returns the value of the property</td>
        </tr>
    </tbody>
</table>


### getNode($relPath)

Returns the Node from a relative Path. Handy to return childnodes.

<table>
    <tbody>
        <tr>
            <td>$relPath</td>
            <td>relative Path to the childnode from the current node</td>
        </tr>
        <tr>
            <td>return</td>
            <td>the node object you chose</td>
        </tr>
    </tbody>
</table>

### getNodes($filter = NULL)

Returns an iterator that contains all childnodes that matched the $filter criterias. It can take either a array or a string.

<table>
    <tbody>
        <tr>
            <td>$filter</td>
            <td>String => "jcr:* | myapp:report | my doc"<br />
                Array => array("jcr:*", "myapp:report", "my doc")<br />
                * is the wildcard<br />
                | is the logical OR<br />
                When passing a string trailing and leading whitespaces will be ignored, not if $filter is an array</td>
        </tr>
        <tr>
            <td>return</td>
            <td>Iterator containing the matching childnodes</td>
        </tr>
    </tbody>
</table>

### getProperty($relPath)

Returns the Property of a node based on a relative path.

<table>
    <tbody>
        <tr>
            <td>$relPath</td>
            <td>relative Path</td>
        </tr>
        <tr>
            <td>return</td>
            <td>Property Object</td>
        </tr>
    </tbody>
</table>

### getPropertyValue($name, $type = NULL)

Returns the Value of a Property identified by it’s name

<table>
    <tbody>
        <tr>
            <td>$name</td>
            <td>The name of the property that shall be returned</td>
        </tr>
        <tr>
            <td>$type</td>
            <td>With this optional parameter you can define a datatype in which the property should be converted.<br />
                Examples<br />
                \PHPCR\PropertyType::LONG => Int<br />
                \PHPCR\PropertyType::STRING => String<br />
                \PHPCR\PropertyType::BOOLEAN => bool<br />
                \PHPCR\PropertyType::DATE => date<br />
                see PHPCR/PropertyType.php for more</td>
        </tr>
    </tbody>
</table>

### getProperties($filter = NULL)

### getDefinition()

### update($srcWorkspace)

### getCorrespondingNodePath($workspaceName)

### getSharedSet()

### removeSharedSet()

### removeShare()

### isCheckedOut()

### isLocked()

### followLifecycleTransition($transition)

### getAllowedLifecycleTransitions()

Property
--------

### setValue($value, $type = NULL, $weak = false)

Sets the value of this property to value. Set a array for multivalue properties.

<table>
    <tbody>
        <tr>
            <td>$value</td>
            <td>Value which has to be set</td>
        </tr>
        <tr>
            <td>$type</td>
            <td>Optional:<br />
                Type request for the property.<br />
                Must be a constant from PropertyType</td>
        </tr>
        <tr>
            <td>$weak</td>
            <td>Optional:<br />
                When a Node is given as $value this can be given as TRUE to create a WEAKREFERENCE.By default a REFERENCE is created</td>
        </tr>
        <tr>
            <td>return</td>
            <td>NOTHING!</td>
        </tr>
    </tbody>
</table>

### getNativeValue()

Returns the value of the PropertyType of this property.

### getString()

Returns a String representation of the value of this property.

### getBinary()

Returns a Binary representation of the value of this property.

### getLong()

Returns an Integer representation of the value of this property.

### getDouble()

Returns a double representation of the value of this property.

### getDecimal()

Returns a BigDecimal representation of the value of this property.

### getDate()

Returns a DateTime representation of the value of this property.

### getBoolean()

Returns a boolean representation of the value of this property.

### getNode()

Returns the referenced node.

### getProperty()

Returns the referenced property.

### getLength()

Returns the length of the value of this property.

### getLengths()

Returns an array holding the lengths of the values of this (multi-value) property in bytes where each is individually calculated as described in getLength().

### getDefinition()

Returns the property definition that applies to this property.

### getType()

<table>
    <tbody>
        <tr>
            <td>return</td>
            <td>Returns the type of this Property. One of:<br />
                PropertyType.STRING<br />
                PropertyType.BINARY<br />
                PropertyType.DATE<br />
                PropertyType.DOUBLE<br />
                PropertyType.LONG<br />
                PropertyType.BOOLEAN<br />
                PropertyType.NAME<br />
                PropertyType.PATH<br />
                PropertyType.REFERENCE<br />
                PropertyType.WEAKREFERENCE<br />
                PropertyType.URI</td>
        </tr>
    </tbody>
</table>

### isMultiple()

Returns true if this property is multi-valued and false if it’s single-valued.