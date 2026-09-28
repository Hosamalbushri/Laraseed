<?php

use Diglactic\Breadcrumbs\Breadcrumbs;
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

// Dashboard > Students
Breadcrumbs::for('students', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(trans('student::app.students.title'), route('admin.students.index'));
});

// Dashboard > Students > Add Student
Breadcrumbs::for('students.create', function (BreadcrumbTrail $trail) {
    $trail->parent('students');
    $trail->push(trans('student::app.students.create.title'), route('admin.students.create'));
});

// Dashboard > Students > [Student Name]
Breadcrumbs::for('students.view', function (BreadcrumbTrail $trail, $student) {
    $trail->parent('students');
    $trail->push(trans('student::app.students.view.title', ['name' => $student->name]), route('admin.students.view', $student->id));
});

// Dashboard > Students > [Student Name] > Edit
Breadcrumbs::for('students.edit', function (BreadcrumbTrail $trail, $student) {
    $trail->parent('students.view', $student);
    $trail->push(trans('student::app.students.edit.title'), route('admin.students.edit', $student->id));
});
