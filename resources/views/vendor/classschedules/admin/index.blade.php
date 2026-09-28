@extends('core::admin.master')

@section('title', __('Classschedules'))

@section('content')


<item-list
    url-base="/api/classschedules"
    locale="{{ config('typicms.content_locale') }}"
    fields="id,status,title,announcement_department_id,year"
    table="classschedules"
    title="classschedules"
    include="image"
    appends="department,formatted_year"
    :exportable="true"
    :searchable="['title']"
    :sorting="['title_translated']"
    >

    <template slot="add-button" v-if="$can('create classschedules')">
        @include('core::admin._button-create', ['module' => 'classschedules'])
    </template>

    <template slot="columns" slot-scope="{ sortArray }">
        <item-list-column-header name="checkbox" v-if="$can('update classschedules')||$can('delete classschedules')"></item-list-column-header>
        <item-list-column-header name="edit" v-if="$can('update classschedules')"></item-list-column-header>
        <item-list-column-header name="title_translated" sortable :sort-array="sortArray" :label="$t('Title')"></item-list-column-header>
        <item-list-column-header name="status_translated" sortable :sort-array="sortArray" :label="$t('Status')"></item-list-column-header>
        <item-list-column-header name="title_translated" sortable :sort-array="sortArray" :label="$t('Department')"></item-list-column-header>
        <item-list-column-header name="title_translated" sortable :sort-array="sortArray" :label="$t('Year')"></item-list-column-header>
    </template>

    <template slot="table-row" slot-scope="{ model, checkedModels, loading }">
        <td class="checkbox" v-if="$can('update classschedules')||$can('delete classschedules')"><item-list-checkbox :model="model" :checked-models-prop="checkedModels" :loading="loading"></item-list-checkbox></td>
        <td v-if="$can('update classschedules')">@include('core::admin._button-edit', ['module' => 'classschedules'])</td>
        <td>@{{ model.title_translated }}</td>
        <td><item-list-status-button :model="model"></item-list-status-button></td>
        <td>@{{ model.department }}</td>
        <td>@{{ model.formatted_year }}</td>


    </template>



</item-list>




@endsection
