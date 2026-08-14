( function() {
	const pathField = document.getElementById( 'questuno-path-id' );
	const groupField = document.getElementById( 'questuno-group-id' );
	const dependencyRows = document.getElementById( 'questuno-dependency-rows' );
	const addDependencyButton = document.getElementById( 'questuno-add-dependency' );
	const dependencyTemplate = document.getElementById( 'tmpl-questuno-dependency-row' );

	if ( ! pathField ) {
		return;
	}

	const syncGroups = function() {
		if ( ! groupField || ! window.questunoCheckpointMetabox ) {
			return;
		}

		const selectedPathId = pathField.value;
		const selectedGroupId = groupField.dataset.selectedGroupId || groupField.value;
		let hasSelectedGroup = false;

		groupField.innerHTML = '';
		groupField.appendChild( new Option( window.questunoCheckpointMetabox.noGroupLabel, '0' ) );

		window.questunoCheckpointMetabox.groups.forEach( function( group ) {
			if ( String( group.path_id ) !== selectedPathId ) {
				return;
			}

			const option = new Option( group.name, String( group.id ) );

			if ( String( group.id ) === selectedGroupId ) {
				option.selected = true;
				hasSelectedGroup = true;
			}

			groupField.appendChild( option );
		} );

		if ( ! hasSelectedGroup ) {
			groupField.value = '0';
		}

		groupField.dataset.selectedGroupId = groupField.value;
	};

	const syncRow = function( row ) {
		const targetTypeField = row.querySelector( '.questuno-dependency-target-type' );
		const checkpointSelect = row.querySelector( '.questuno-dependency-checkpoint' );
		const groupSelect = row.querySelector( '.questuno-dependency-group' );
		const selectedPathId = pathField.value;
		let hasSelectedCheckpoint = false;
		let hasSelectedGroup = false;

		if ( ! targetTypeField || ! checkpointSelect || ! groupSelect ) {
			return;
		}

		Array.from( checkpointSelect.options ).forEach( function( option, index ) {
			if ( 0 === index ) {
				return;
			}

			const matchesPath = option.dataset.pathId === selectedPathId;
			option.hidden = ! matchesPath;
			option.disabled = ! matchesPath;
			hasSelectedCheckpoint = hasSelectedCheckpoint || ( matchesPath && option.selected );
		} );

		if ( ! hasSelectedCheckpoint ) {
			checkpointSelect.value = '0';
		}

		Array.from( groupSelect.options ).forEach( function( option, index ) {
			if ( 0 === index ) {
				return;
			}

			const matchesPath = option.dataset.pathId === selectedPathId;
			option.hidden = ! matchesPath;
			option.disabled = ! matchesPath;
			hasSelectedGroup = hasSelectedGroup || ( matchesPath && option.selected );
		} );

		if ( ! hasSelectedGroup ) {
			groupSelect.value = '0';
		}

		checkpointSelect.hidden = 'checkpoint' !== targetTypeField.value;
		groupSelect.hidden = 'group' !== targetTypeField.value;
	};

	const bindRow = function( row ) {
		const targetTypeField = row.querySelector( '.questuno-dependency-target-type' );
		const removeButton = row.querySelector( '.questuno-remove-dependency' );

		if ( ! targetTypeField || ! removeButton ) {
			return;
		}

		targetTypeField.addEventListener( 'change', function() {
			syncRow( row );
		} );
		removeButton.addEventListener( 'click', function() {
			row.remove();
		} );
		syncRow( row );
	};

	const syncRows = function() {
		if ( ! dependencyRows ) {
			return;
		}

		Array.from( dependencyRows.querySelectorAll( '.questuno-dependency-row' ) ).forEach( syncRow );
	};

	if ( dependencyRows && addDependencyButton && dependencyTemplate ) {
		Array.from( dependencyRows.querySelectorAll( '.questuno-dependency-row' ) ).forEach( bindRow );
		addDependencyButton.addEventListener( 'click', function() {
			const index = dependencyRows.querySelectorAll( '.questuno-dependency-row' ).length;
			const content = dependencyTemplate.content.cloneNode( true );
			const wrapper = document.createElement( 'div' );

			wrapper.appendChild( content );
			wrapper.innerHTML = wrapper.innerHTML.replaceAll( '__index__', index );
			dependencyRows.insertAdjacentHTML( 'beforeend', wrapper.innerHTML );
			bindRow( dependencyRows.lastElementChild );
		} );
	}

	pathField.addEventListener( 'change', function() {
		syncGroups();
		syncRows();
	} );
	syncGroups();
	syncRows();
}() );
