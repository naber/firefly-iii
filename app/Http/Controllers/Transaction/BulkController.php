<?php
/**
 * BulkController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Transaction;


use ExpandedForm;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\BulkEditJournalRequest;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Preferences;
use View;

/**
 * Class BulkController
 */
class BulkController extends Controller
{
    /** @var JournalRepositoryInterface */
    private $repository;


    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                $this->repository = app(JournalRepositoryInterface::class);
                app('view')->share('title', trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-repeat');

                return $next($request);
            }
        );
    }

    /**
     * @param Collection $journals
     *
     * @return View
     */
    public function edit(Collection $journals)
    {
        $subTitle = trans('firefly.mass_bulk_journals');

        // get list of budgets:
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $budgetList = ExpandedForm::makeSelectListWithEmpty($repository->getActiveBudgets());
        // collect some useful meta data for the mass edit:
        $journals->each(
            function (TransactionJournal $journal) {
                $journal->transaction_count = $journal->transactions()->count();
            }
        );

        return view('transactions.bulk.edit', compact('journals', 'subTitle', 'budgetList'));
    }


    /**
     * @param BulkEditJournalRequest     $request
     *
     * @return mixed
     */
    public function update(BulkEditJournalRequest $request)
    {
        $journalIds     = $request->get('journals');
        $journalIds     = \is_array($journalIds) ? $journalIds : [];
        $ignoreCategory = (int)$request->get('ignore_category') === 1;
        $ignoreBudget   = (int)$request->get('ignore_budget') === 1;
        $ignoreTags     = (int)$request->get('ignore_tags') === 1;
        $count          = 0;

        foreach ($journalIds as $journalId) {
            $journal = $this->repository->findNull((int)$journalId);
            if (null === $journal) {
                continue;
            }

            $count++;
            Log::debug(sprintf('Found journal #%d', $journal->id));

            // update category if not told to ignore
            if ($ignoreCategory === false) {
                Log::debug(sprintf('Set category to %s', $request->string('category')));

                $this->repository->updateCategory($journal, $request->string('category'));
            }

            // update budget if not told to ignore (and is withdrawal)
            if ($ignoreBudget === false) {
                Log::debug(sprintf('Set budget to %d', $request->integer('budget_id')));
                $this->repository->updateBudget($journal, $request->integer('budget_id'));
            }

            // update tags:
            if ($ignoreTags === false) {
                Log::debug(sprintf('Set tags to %s', $request->string('budget_id')));
                $this->repository->updateTags($journal, ['tags' => explode(',', $request->string('tags'))]);
            }
        }

        Preferences::mark();
        $request->session()->flash('success', trans('firefly.mass_edited_transactions_success', ['amount' => $count]));

        // redirect to previous URL:
        return redirect($this->getPreviousUri('transactions.bulk-edit.uri'));
    }
}
