define(function (require) {
    'use strict';
    var FlowchartEditorWorkflowView,
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        mediator = require('oroui/js/mediator'),
        FlowchartViewerWorkflowView = require('../viewer/workflow-view'),
        FlowChartEditorTransitionOverlayView = require('./transition-overlay-view'),
        FlowchartEditorStepView = require('./step-view');

    FlowchartEditorWorkflowView = FlowchartViewerWorkflowView.extend({

        isConnected: false,

        transitionOverlayView: FlowChartEditorTransitionOverlayView,
        stepView: FlowchartEditorStepView,
        className: 'workflow-flowchart-editor',

        defaultConnectionConfiguration: {
            detachable: true
        },

        connect: function () {
            FlowchartEditorWorkflowView.__super__.connect.apply(this, arguments);
            this.jsPlumbInstance.bind('beforeDrop', _.bind(this.onBeforeConnectionDrop, this));
        },

        onBeforeConnectionDrop: function (data) {
            var transitionModel, startingSteps, suspendedStep,
                stepFrom = this.findStepModelByElement(data.connection.source),
                stepTo = this.findStepModelByElement(data.connection.target);
            if (data.connection.suspendedElement && !stepTo.get('_is_start')) {
                transitionModel = data.connection.overlayView.model;
                startingSteps = transitionModel.getStartingSteps();
                if (stepTo.get('name') !== transitionModel.get('step_to')) {
                    // stepTo changed
                    transitionModel.set({
                        step_to: stepTo.get('name')
                    });
                }
                if (startingSteps.indexOf(stepFrom) === -1) {
                    suspendedStep = this.findStepModelByElement(data.connection.suspendedElement);
                    stepFrom.getAllowedTransitions().add(transitionModel);
                    suspendedStep.getAllowedTransitions().remove(transitionModel);
                    stepFrom.trigger('change');
                    suspendedStep.trigger('change');
                }
            } else if (!stepTo.get('_is_start')) {
                this.model.trigger('requestAddTransition', stepFrom, stepTo);
            } else {
                mediator.execute(
                    'showFlashMessage',
                    'error',
                    __(
                        'Cannot set end step to <i>(Start)</i> step. Please select another one'
                    )
                );
            }
            // never allow jsplumb just draw new connections, create connection model instead
            return false;
        }
    });

    return FlowchartEditorWorkflowView;
});
